import {
  test,
  expect,
  type APIResponse,
  type Browser,
  type Page,
} from "@playwright/test";
import { loginUser, createUser, baseURL } from "../helpers";

// Grant endpoints under DrawerPermissions.php, routed at
// /{instance}/drawerPermissions/grants[/{id}]. A grant gives one drawer
// group a permission level on one drawer. Grants are scoped by drawer, not
// group owner: the listing spans every drawer the caller can manage, and
// update/delete work on any grant there, including grants for groups owned
// by other users. Only creation is limited to the caller's own groups.
//
// reset-test-db.sh never truncates users, groups, or grants, so fixture users
// are find-or-create and labels carry a per-run tag.

const runTag = Date.now().toString(36);

function uniqueLabel(name: string): string {
  return `${name} ${runTag}`;
}

const NON_MANAGER = {
  username: "e2e-no-drawer-access",
  password: "e2e-no-drawer-access",
};

// granted instance-wide createDrawers via a group, not admin
const DRAWER_MANAGER = {
  username: "e2e-drawer-manager",
  password: "e2e-drawer-manager",
};

const MANAGER_GRANT_GROUP_LABEL = "Drawer Managers (e2e)";

async function loginAdmin(page: Page) {
  await loginUser(page, "admin");
}

type UserMatch = {
  name: string;
  email: string;
  localUserId: number;
  username: string;
};

async function findUserByUsername(
  page: Page,
  username: string,
): Promise<UserMatch> {
  const res = await page.request.get(
    `${baseURL()}/drawerPermissions/userAutocomplete?q=${encodeURIComponent(
      username,
    )}`,
    { headers: { Accept: "application/json" } },
  );
  expect(res.status()).toBe(200);
  const { matches } = (await res.json()) as { matches: UserMatch[] };
  const match = matches.find((m) => m.username === username);
  expect(match, `no autocomplete match for "${username}"`).toBeTruthy();
  return match as UserMatch;
}

type PermissionLevel = { id: number; level: number };

// Levels arrive sorted ascending, so this is the strongest level a drawer
// grant accepts: grants cap at originals (level 40), matching legacy.
function highestGrantableLevel(levels: PermissionLevel[]): PermissionLevel {
  const grantable = levels.filter((l) => Number(l.level) <= 40);
  return grantable[grantable.length - 1];
}

// /permissions/permissionLevels is open to any authed user, unlike the
// admin-only /adminPermissions one, so a drawer manager can read it too.
async function getPermissionLevels(page: Page): Promise<PermissionLevel[]> {
  const res = await page.request.get(
    `${baseURL()}/permissions/permissionLevels`,
    { headers: { Accept: "application/json" } },
  );
  expect(res.status()).toBe(200);
  const { permissionLevels } = (await res.json()) as {
    permissionLevels: PermissionLevel[];
  };
  return permissionLevels;
}

async function ensureNonManagerUser(browser: Browser): Promise<void> {
  const context = await browser.newContext();
  const page = await context.newPage();
  await loginAdmin(page);
  await createUser(page, NON_MANAGER.username, NON_MANAGER.password);
  await context.close();
}

// The manager's createDrawers level rides on an instance group with them as
// its one member, mirroring drawerGroups.spec.ts so the two specs share the
// find-or-create fixture.
async function ensureDrawerManagerUser(browser: Browser): Promise<void> {
  const context = await browser.newContext();
  const page = await context.newPage();
  await loginAdmin(page);
  await createUser(page, DRAWER_MANAGER.username, DRAWER_MANAGER.password);

  const listRes = await page.request.get(
    `${baseURL()}/adminPermissions/groups`,
    { headers: { Accept: "application/json" } },
  );
  expect(listRes.status()).toBe(200);
  const { groups } = (await listRes.json()) as {
    groups: { id: number; label: string }[];
  };

  if (groups.some((g) => g.label === MANAGER_GRANT_GROUP_LABEL)) {
    await context.close();
    return;
  }

  const createRes = await page.request.post(
    `${baseURL()}/adminPermissions/groups`,
    { form: { type: "User", label: MANAGER_GRANT_GROUP_LABEL } },
  );
  expect(createRes.status()).toBe(201);
  const { group } = (await createRes.json()) as { group: { id: number } };

  const manager = await findUserByUsername(page, DRAWER_MANAGER.username);
  const memberRes = await page.request.post(
    `${baseURL()}/adminPermissions/groups/${group.id}/members`,
    { form: { localUserId: String(manager.localUserId) } },
  );
  expect(memberRes.status()).toBe(201);

  const levels = await getPermissionLevels(page);
  const createDrawers = levels.find((p) => Number(p.level) === 30);
  expect(createDrawers, "seed DB lacks the createDrawers level").toBeTruthy();

  const grantRes = await page.request.post(
    `${baseURL()}/adminPermissions/instanceGrants`,
    {
      form: {
        groupId: String(group.id),
        permissionLevelId: String(createDrawers?.id),
      },
    },
  );
  expect(grantRes.status()).toBe(201);

  await context.close();
}

// Drawers::addDrawer auto-creates the owner's personal drawer group and a
// createDrawers grant for it, so a fresh drawer already has one grant.
async function createDrawer(page: Page, title: string): Promise<number> {
  const res = await page.request.post(`${baseURL()}/drawers/addDrawer`, {
    headers: { Accept: "application/json" },
    form: { drawerTitle: title },
  });
  expect(res.status()).toBe(200);
  return (await res.json()).drawerId as number;
}

async function createDrawerGroup(page: Page, label: string): Promise<number> {
  const res = await page.request.post(`${baseURL()}/drawerPermissions/groups`, {
    form: { type: "User", label },
  });
  expect(res.status()).toBe(201);
  return (await res.json()).group.id as number;
}

type Grant = {
  id: number;
  drawerId: number;
  permissionLevelId: number;
  group: {
    id: number;
    label: string;
    type: string;
    ownedByCurrentUser: boolean;
    ownerName: string | null;
  } | null;
};

async function listGrants(page: Page): Promise<Grant[]> {
  const res = await page.request.get(`${baseURL()}/drawerPermissions/grants`, {
    headers: { Accept: "application/json" },
  });
  expect(res.status()).toBe(200);
  return (await res.json()).grants as Grant[];
}

async function createGrant(
  page: Page,
  drawerId: number,
  drawerGroupId: number,
  permissionLevelId: number,
): Promise<APIResponse> {
  return page.request.post(`${baseURL()}/drawerPermissions/grants`, {
    form: {
      drawerId: String(drawerId),
      drawerGroupId: String(drawerGroupId),
      permissionLevelId: String(permissionLevelId),
    },
  });
}

test.describe("drawerPermissions grants", () => {
  test.describe("unauthenticated", () => {
    for (const path of [
      "/drawerPermissions/grants",
      "/drawerPermissions/grants/1",
    ]) {
      test(`GET ${path} returns 401`, async ({ page }) => {
        const res = await page.request.get(`${baseURL()}${path}`, {
          headers: { Accept: "application/json" },
        });
        expect(res.status()).toBe(401);
      });
    }
  });

  test.describe("without drawer access", () => {
    test.beforeAll(async ({ browser }) => {
      await ensureNonManagerUser(browser);
    });

    test.beforeEach(async ({ page }) => {
      await loginUser(page, NON_MANAGER.username, NON_MANAGER.password);
    });

    test("GET grants returns 403", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/drawerPermissions/grants`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(403);
    });
  });

  test.describe("as a drawer manager", () => {
    test.beforeAll(async ({ browser }) => {
      await ensureDrawerManagerUser(browser);
      await ensureNonManagerUser(browser);
    });

    test.beforeEach(async ({ page }) => {
      await loginUser(page, DRAWER_MANAGER.username, DRAWER_MANAGER.password);
    });

    test("a fresh drawer lists its auto-created personal grant", async ({
      page,
    }) => {
      const drawerId = await createDrawer(page, uniqueLabel("Fresh Drawer"));
      const drawerGrants = (await listGrants(page)).filter(
        (g) => g.drawerId === drawerId,
      );
      expect(drawerGrants.length).toBeGreaterThanOrEqual(1);
      expect(drawerGrants.every((g) => g.group?.ownedByCurrentUser)).toBe(true);
    });

    test("creates, lists, re-levels, and deletes a grant", async ({ page }) => {
      const drawerId = await createDrawer(page, uniqueLabel("CRUD Drawer"));
      const groupId = await createDrawerGroup(page, uniqueLabel("CRUD Group"));
      const levels = await getPermissionLevels(page);

      const created = await createGrant(page, drawerId, groupId, levels[0].id);
      expect(created.status()).toBe(201);
      const grant = (await created.json()).grant as Grant;
      expect(grant.drawerId).toBe(drawerId);
      expect(grant.group?.id).toBe(groupId);
      expect(grant.permissionLevelId).toBe(levels[0].id);

      const afterCreate = await listGrants(page);
      expect(afterCreate.some((g) => g.id === grant.id)).toBe(true);

      const newLevel = highestGrantableLevel(levels);
      const updated = await page.request.put(
        `${baseURL()}/drawerPermissions/grants/${grant.id}`,
        { form: { permissionLevelId: String(newLevel.id) } },
      );
      expect(updated.status()).toBe(200);
      expect((await updated.json()).grant.permissionLevelId).toBe(newLevel.id);

      const removed = await page.request.delete(
        `${baseURL()}/drawerPermissions/grants/${grant.id}`,
        { headers: { Accept: "application/json" } },
      );
      expect(removed.status()).toBe(200);
      expect((await removed.json()).removed).toBe(grant.id);

      const afterDelete = await listGrants(page);
      expect(afterDelete.some((g) => g.id === grant.id)).toBe(false);
    });

    test("rejects a level above originals on create and update", async ({
      page,
    }) => {
      const drawerId = await createDrawer(page, uniqueLabel("Capped Drawer"));
      const groupId = await createDrawerGroup(page, uniqueLabel("Capped Group"));
      const levels = await getPermissionLevels(page);

      const found = levels.find((l) => Number(l.level) > 40);
      expect(found, "seed DB lacks a level above originals").toBeTruthy();
      const levelAboveOriginals = found as PermissionLevel;

      const created = await createGrant(
        page,
        drawerId,
        groupId,
        levelAboveOriginals.id,
      );
      expect(created.status()).toBe(422);

      const grantable = highestGrantableLevel(levels);
      const allowed = await createGrant(page, drawerId, groupId, grantable.id);
      expect(allowed.status()).toBe(201);
      const grantId = (await allowed.json()).grant.id as number;

      const updated = await page.request.put(
        `${baseURL()}/drawerPermissions/grants/${grantId}`,
        { form: { permissionLevelId: String(levelAboveOriginals.id) } },
      );
      expect(updated.status()).toBe(422);

      // the refused update leaves the grant at its old level
      const survivor = (await listGrants(page)).find((g) => g.id === grantId);
      expect(survivor?.permissionLevelId).toBe(grantable.id);
    });

    test("re-levels but cannot delete another owner's grant on a managed drawer", async ({
      page,
      browser,
    }) => {
      const drawerId = await createDrawer(page, uniqueLabel("Shared Drawer"));
      const levels = await getPermissionLevels(page);

      // the admin (who manages every drawer) grants their own group access
      // to the manager's drawer
      const adminContext = await browser.newContext();
      const adminPage = await adminContext.newPage();
      await loginAdmin(adminPage);
      const adminGroupId = await createDrawerGroup(
        adminPage,
        uniqueLabel("Admin Group"),
      );
      const created = await createGrant(
        adminPage,
        drawerId,
        adminGroupId,
        levels[0].id,
      );
      expect(created.status()).toBe(201);
      const adminGrant = (await created.json()).grant as Grant;
      const adminName = (await findUserByUsername(adminPage, "admin")).name;
      await adminContext.close();

      // the manager sees the grant, labeled with the owner it belongs to
      const listed = (await listGrants(page)).find(
        (g) => g.id === adminGrant.id,
      );
      expect(listed).toBeTruthy();
      expect(listed?.group?.ownedByCurrentUser).toBe(false);
      expect(listed?.group?.ownerName).toBe(adminName);

      // and can re-level it despite not owning the group
      const newLevel = highestGrantableLevel(levels);
      const updated = await page.request.put(
        `${baseURL()}/drawerPermissions/grants/${adminGrant.id}`,
        { form: { permissionLevelId: String(newLevel.id) } },
      );
      expect(updated.status()).toBe(200);

      // but not delete it, which they could not undo: a new grant can
      // only name a group they own. Re-levelling to noperm is the revoke.
      const removed = await page.request.delete(
        `${baseURL()}/drawerPermissions/grants/${adminGrant.id}`,
        { headers: { Accept: "application/json" } },
      );
      expect(removed.status()).toBe(403);

      // the grant survives the refused delete
      const survivor = (await listGrants(page)).find(
        (g) => g.id === adminGrant.id,
      );
      expect(survivor?.permissionLevelId).toBe(newLevel.id);
    });

    test("lets an admin delete a grant on someone else's group", async ({
      page,
      browser,
    }) => {
      // the manager grants their own group access to their own drawer
      const drawerId = await createDrawer(page, uniqueLabel("Managed Drawer"));
      const groupId = await createDrawerGroup(page, uniqueLabel("Their Group"));
      const levels = await getPermissionLevels(page);
      const created = await createGrant(page, drawerId, groupId, levels[0].id);
      expect(created.status()).toBe(201);
      const managerGrant = (await created.json()).grant as Grant;

      // the admin does not own that group, but reaches every drawer
      const adminContext = await browser.newContext();
      const adminPage = await adminContext.newPage();
      await loginAdmin(adminPage);

      const listed = (await listGrants(adminPage)).find(
        (g) => g.id === managerGrant.id,
      );
      expect(listed?.group?.ownedByCurrentUser).toBe(false);

      const removed = await adminPage.request.delete(
        `${baseURL()}/drawerPermissions/grants/${managerGrant.id}`,
        { headers: { Accept: "application/json" } },
      );
      expect(removed.status()).toBe(200);
      await adminContext.close();

      expect((await listGrants(page)).some((g) => g.id === managerGrant.id)).toBe(
        false,
      );
    });

    // The flat /grants/{id} route makes every grant id addressable, so the
    // per-drawer check is the only thing scoping a manager to their own
    // drawers. The route gate passes anyone who manages any drawer at all.
    test("rejects mutating a grant on a drawer the caller cannot manage", async ({
      page,
      browser,
    }) => {
      // the admin's own drawer, which the manager holds no permission on
      const adminContext = await browser.newContext();
      const adminPage = await adminContext.newPage();
      await loginAdmin(adminPage);
      const adminDrawerId = await createDrawer(
        adminPage,
        uniqueLabel("Admin Only Drawer"),
      );
      // addDrawer auto-creates the owner's personal grant on it
      const adminGrant = (await listGrants(adminPage)).find(
        (g) => g.drawerId === adminDrawerId,
      );
      expect(adminGrant, "admin's fresh drawer has no grant to target").toBeTruthy();
      const levels = await getPermissionLevels(adminPage);
      await adminContext.close();

      // the manager can manage some drawer, so they clear the route gate,
      // but this grant's drawer is not one of them
      expect((await listGrants(page)).some((g) => g.id === adminGrant?.id)).toBe(
        false,
      );

      const updated = await page.request.put(
        `${baseURL()}/drawerPermissions/grants/${adminGrant?.id}`,
        { form: { permissionLevelId: String(levels[0].id) } },
      );
      expect(updated.status()).toBe(403);

      const removed = await page.request.delete(
        `${baseURL()}/drawerPermissions/grants/${adminGrant?.id}`,
        { headers: { Accept: "application/json" } },
      );
      expect(removed.status()).toBe(403);
    });

    test("rejects a second grant for the same group with 409", async ({
      page,
    }) => {
      const drawerId = await createDrawer(page, uniqueLabel("Dupe Drawer"));
      const groupId = await createDrawerGroup(page, uniqueLabel("Dupe Group"));
      const levels = await getPermissionLevels(page);

      const first = await createGrant(page, drawerId, groupId, levels[0].id);
      expect(first.status()).toBe(201);
      const existingId = (await first.json()).grant.id;

      const second = await createGrant(page, drawerId, groupId, levels[0].id);
      expect(second.status()).toBe(409);
      expect((await second.json()).existingGrantId).toBe(existingId);
    });

    test("rejects an unknown group or level with 422, unknown drawer with 404", async ({
      page,
    }) => {
      const drawerId = await createDrawer(
        page,
        uniqueLabel("Validation Drawer"),
      );
      const groupId = await createDrawerGroup(page, uniqueLabel("Val Group"));
      const levels = await getPermissionLevels(page);

      const badGroup = await createGrant(page, drawerId, 99999999, levels[0].id);
      expect(badGroup.status()).toBe(422);

      const badLevel = await createGrant(page, drawerId, groupId, 99999999);
      expect(badLevel.status()).toBe(422);

      const badDrawer = await createGrant(page, 99999999, groupId, levels[0].id);
      expect(badDrawer.status()).toBe(404);
    });

    test("rejects a grant naming another user's real group with 422", async ({
      page,
      browser,
    }) => {
      const drawerId = await createDrawer(page, uniqueLabel("Own Drawer"));
      const levels = await getPermissionLevels(page);

      const adminContext = await browser.newContext();
      const adminPage = await adminContext.newPage();
      await loginAdmin(adminPage);
      const foreignGroupId = await createDrawerGroup(
        adminPage,
        uniqueLabel("Foreign Group"),
      );
      await adminContext.close();

      // same refusal as a bogus id: another owner's group is not findable
      const res = await createGrant(
        page,
        drawerId,
        foreignGroupId,
        levels[0].id,
      );
      expect(res.status()).toBe(422);
    });

    test("updating or deleting a missing grant returns 404", async ({
      page,
    }) => {
      const levels = await getPermissionLevels(page);

      const update = await page.request.put(
        `${baseURL()}/drawerPermissions/grants/99999999`,
        { form: { permissionLevelId: String(levels[0].id) } },
      );
      expect(update.status()).toBe(404);

      const remove = await page.request.delete(
        `${baseURL()}/drawerPermissions/grants/99999999`,
        { headers: { Accept: "application/json" } },
      );
      expect(remove.status()).toBe(404);
    });

    test("returns 400 for a non-numeric id", async ({ page }) => {
      const res = await page.request.delete(
        `${baseURL()}/drawerPermissions/grants/not-a-number`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(400);
    });

    test("a grant opens the drawer to the group's members until deleted", async ({
      page,
      browser,
    }) => {
      const drawerId = await createDrawer(page, uniqueLabel("Access Drawer"));
      const groupId = await createDrawerGroup(page, uniqueLabel("Access Group"));

      const member = await findUserByUsername(page, NON_MANAGER.username);
      const memberRes = await page.request.post(
        `${baseURL()}/drawerPermissions/groups/${groupId}/members`,
        { form: { localUserId: String(member.localUserId) } },
      );
      expect(memberRes.status()).toBe(201);

      // a viewing level, so the member gains the drawer without becoming
      // a drawer manager and breaking the without-drawer-access tests
      const levels = await getPermissionLevels(page);
      const viewLevel = levels.find(
        (l) => Number(l.level) > 0 && Number(l.level) < 30,
      );
      expect(viewLevel, "seed DB lacks a sub-manage level").toBeTruthy();

      const created = await createGrant(
        page,
        drawerId,
        groupId,
        (viewLevel as PermissionLevel).id,
      );
      expect(created.status()).toBe(201);
      const grantId = (await created.json()).grant.id as number;

      const memberContext = await browser.newContext();
      const memberPage = await memberContext.newPage();
      await loginUser(memberPage, NON_MANAGER.username, NON_MANAGER.password);

      const withGrant = await memberPage.request.get(
        `${baseURL()}/drawers/listDrawers/true`,
        { headers: { Accept: "application/json" } },
      );
      expect(withGrant.status()).toBe(200);
      const visibleDrawers = (await withGrant.json()) as Record<
        string,
        { title: string }
      >;
      expect(Object.keys(visibleDrawers)).toContain(String(drawerId));

      const removed = await page.request.delete(
        `${baseURL()}/drawerPermissions/grants/${grantId}`,
        { headers: { Accept: "application/json" } },
      );
      expect(removed.status()).toBe(200);

      const withoutGrant = await memberPage.request.get(
        `${baseURL()}/drawers/listDrawers/true`,
        { headers: { Accept: "application/json" } },
      );
      expect(withoutGrant.status()).toBe(200);
      const remainingDrawers = (await withoutGrant.json()) as Record<
        string,
        { title: string }
      >;
      expect(Object.keys(remainingDrawers)).not.toContain(String(drawerId));
      await memberContext.close();
    });
  });
});

test.describe("drawerPermissions manageableDrawers", () => {
  test.beforeAll(async ({ browser }) => {
    await ensureDrawerManagerUser(browser);
  });

  test.beforeEach(async ({ page }) => {
    await loginUser(page, DRAWER_MANAGER.username, DRAWER_MANAGER.password);
  });

  test("lists the caller's manageable drawers", async ({ page }) => {
    const title = uniqueLabel("Listable Drawer");
    const drawerId = await createDrawer(page, title);

    const res = await page.request.get(
      `${baseURL()}/drawerPermissions/manageableDrawers`,
      { headers: { Accept: "application/json" } },
    );
    expect(res.status()).toBe(200);

    const { manageableDrawers } = (await res.json()) as {
      manageableDrawers: { id: number; title: string }[];
    };
    const created = manageableDrawers.find((d) => d.id === drawerId);
    expect(created?.title).toBe(title);
  });

  test("rejects a non-GET verb with 405", async ({ page }) => {
    const res = await page.request.post(
      `${baseURL()}/drawerPermissions/manageableDrawers`,
      { form: {} },
    );
    expect(res.status()).toBe(405);
  });

  test("an admin sees drawers beyond their own", async ({ page, browser }) => {
    const managerDrawerId = await createDrawer(
      page,
      uniqueLabel("Manager Reach Drawer"),
    );

    const adminContext = await browser.newContext();
    const adminPage = await adminContext.newPage();
    await loginAdmin(adminPage);
    const adminDrawerId = await createDrawer(
      adminPage,
      uniqueLabel("Admin Reach Drawer"),
    );

    const adminRes = await adminPage.request.get(
      `${baseURL()}/drawerPermissions/manageableDrawers`,
      { headers: { Accept: "application/json" } },
    );
    expect(adminRes.status()).toBe(200);
    const adminList = (await adminRes.json()) as {
      manageableDrawers: { id: number }[];
    };
    expect(
      adminList.manageableDrawers.some((d) => d.id === managerDrawerId),
    ).toBe(true);
    await adminContext.close();

    // the manager's reach stays limited to their own drawers
    const managerRes = await page.request.get(
      `${baseURL()}/drawerPermissions/manageableDrawers`,
      { headers: { Accept: "application/json" } },
    );
    expect(managerRes.status()).toBe(200);
    const managerList = (await managerRes.json()) as {
      manageableDrawers: { id: number }[];
    };
    expect(
      managerList.manageableDrawers.some((d) => d.id === adminDrawerId),
    ).toBe(false);
  });
});

test.describe("permission levels", () => {
  test.beforeAll(async ({ browser }) => {
    await ensureDrawerManagerUser(browser);
  });

  test.beforeEach(async ({ page }) => {
    await loginUser(page, DRAWER_MANAGER.username, DRAWER_MANAGER.password);
  });

  // level is a string column upstream, so "10" would sort before "5"
  // without the numeric comparison
  test("arrive in ascending numeric order", async ({ page }) => {
    const levels = await getPermissionLevels(page);
    expect(levels.length).toBeGreaterThan(1);

    const numericLevels = levels.map((l) => Number(l.level));
    const ascending = [...numericLevels].sort((a, b) => a - b);
    expect(numericLevels).toEqual(ascending);
  });
});
