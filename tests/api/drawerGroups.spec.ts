import { test, expect, type Browser, type Page } from "@playwright/test";
import { loginUser, createUser, baseURL } from "../helpers";

// Endpoints under DrawerPermissions.php, routed at /{instance}/drawerPermissions.
// Open to any user who can manage drawers, not only admins, with every
// group action scoped to the signed-in user's own groups.
//
// reset-test-db.sh never truncates users, groups, or grants, so fixture
// users are find-or-create and group labels carry a per-run tag.

const runTag = Date.now().toString(36);

function uniqueLabel(name: string): string {
  return `${name} ${runTag}`;
}

// never granted anything, for the 403 cases
const NON_MANAGER = {
  username: "e2e-no-drawer-access",
  password: "e2e-no-drawer-access",
};

// granted instance-wide createDrawers, but not admin
const DRAWER_MANAGER = {
  username: "e2e-drawer-manager",
  password: "e2e-drawer-manager",
};

// the instance group that carries DRAWER_MANAGER's grant
const MANAGER_GRANT_GROUP_LABEL = "Drawer Managers (e2e)";

async function loginAdmin(page: Page) {
  await loginUser(page, "admin");
}

async function ensureNonManagerUser(browser: Browser): Promise<void> {
  const context = await browser.newContext();
  const page = await context.newPage();
  await loginAdmin(page);
  await createUser(page, NON_MANAGER.username, NON_MANAGER.password);
  await context.close();
}

// Instance grants attach to groups, not users, so the manager's
// createDrawers level rides on a group with them as its one member.
async function ensureDrawerManagerUser(browser: Browser): Promise<void> {
  const context = await browser.newContext();
  const page = await context.newPage();
  await loginAdmin(page);
  await createUser(page, DRAWER_MANAGER.username, DRAWER_MANAGER.password);

  const listRes = await page.request.get(
    `${baseURL()}/adminPermissions/groups`,
    { headers: { Accept: "application/json" } }
  );
  expect(listRes.status()).toBe(200);
  const { groups } = (await listRes.json()) as {
    groups: { id: number; label: string }[];
  };

  // group present means a previous run finished the member + grant too
  if (groups.some((g) => g.label === MANAGER_GRANT_GROUP_LABEL)) {
    await context.close();
    return;
  }

  const createRes = await page.request.post(
    `${baseURL()}/adminPermissions/groups`,
    { form: { type: "User", label: MANAGER_GRANT_GROUP_LABEL } }
  );
  expect(createRes.status()).toBe(201);
  const { group } = (await createRes.json()) as { group: { id: number } };

  const manager = await findUserByUsername(page, DRAWER_MANAGER.username);
  const memberRes = await page.request.post(
    `${baseURL()}/adminPermissions/groups/${group.id}/members`,
    { form: { localUserId: String(manager.localUserId) } }
  );
  expect(memberRes.status()).toBe(201);

  const levelsRes = await page.request.get(
    `${baseURL()}/adminPermissions/permissionLevels`,
    { headers: { Accept: "application/json" } }
  );
  expect(levelsRes.status()).toBe(200);
  const { permissionLevels } = (await levelsRes.json()) as {
    permissionLevels: { id: number; level: string }[];
  };
  // PERM_CREATEDRAWERS in constants.php
  const createDrawers = permissionLevels.find((p) => Number(p.level) === 30);
  expect(createDrawers, "seed DB lacks the createDrawers level").toBeTruthy();

  const grantRes = await page.request.post(
    `${baseURL()}/adminPermissions/instanceGrants`,
    {
      form: {
        groupId: String(group.id),
        permissionLevelId: String(createDrawers?.id),
      },
    }
  );
  expect(grantRes.status()).toBe(201);

  await context.close();
}

type Group = {
  id: number;
  type: string;
  label: string;
  entries_count: number;
};

async function createDrawerGroup(
  page: Page,
  form: Record<string, string>
): Promise<Group> {
  const res = await page.request.post(`${baseURL()}/drawerPermissions/groups`, {
    form,
  });
  expect(res.status()).toBe(201);
  return (await res.json()).group as Group;
}

type UserMatch = {
  name: string;
  email: string;
  localUserId: number;
  username: string;
};

// resolve ids at runtime instead of hard-coding seed ids
async function findUserByUsername(
  page: Page,
  username: string
): Promise<UserMatch> {
  const res = await page.request.get(
    `${baseURL()}/adminPermissions/userAutocomplete?q=${encodeURIComponent(
      username
    )}`,
    { headers: { Accept: "application/json" } }
  );
  expect(res.status()).toBe(200);
  const { matches } = (await res.json()) as { matches: UserMatch[] };
  const match = matches.find((m) => m.username === username);
  expect(match, `no autocomplete match for "${username}"`).toBeTruthy();
  return match as UserMatch;
}

type GroupEntry = {
  id: number;
  value: string;
};

// Entries only exist on auth-helper group types, and the instance's helper
// may define none (the base AuthHelper is empty). CI runs MockAuthHelper,
// which defines the UMN types. Discover a type at runtime so entry tests
// can skip on a bare instance instead of failing.
async function findAuthHelperGroupType(page: Page): Promise<string | null> {
  const res = await page.request.get(
    `${baseURL()}/drawerPermissions/groupTypes`,
    { headers: { Accept: "application/json" } }
  );
  expect(res.status()).toBe(200);
  const { groupTypes } = (await res.json()) as {
    groupTypes: { type: string }[];
  };
  const builtinTypes = ["All", "Authed", "Authed_remote", "User"];
  const isAuthHelperType = (g: { type: string }) =>
    !builtinTypes.includes(g.type);
  return groupTypes.find(isAuthHelperType)?.type ?? null;
}

test.describe("drawerPermissions", () => {
  test.describe("unauthenticated", () => {
    for (const path of [
      "/drawerPermissions/groupTypes",
      "/drawerPermissions/userAutocomplete?q=ab",
      "/drawerPermissions/drawers",
      "/drawerPermissions/groups",
      "/drawerPermissions/groups/1",
      "/drawerPermissions/groups/1/members",
      "/drawerPermissions/groups/1/entries",
    ]) {
      test(`GET ${path} returns 401`, async ({ page }) => {
        const res = await page.request.get(`${baseURL()}${path}`, {
          headers: { Accept: "application/json" },
        });
        expect(res.status()).toBe(401);
      });
    }
  });

  test.describe("authenticated without drawer access", () => {
    test.beforeAll(async ({ browser }) => {
      await ensureNonManagerUser(browser);
    });

    test.beforeEach(async ({ page }) => {
      await loginUser(page, NON_MANAGER.username, NON_MANAGER.password);
    });

    test("GET /groups returns 403", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/drawerPermissions/groups`,
        { headers: { Accept: "application/json" } }
      );
      expect(res.status()).toBe(403);
    });

    test("POST /groups returns 403", async ({ page }) => {
      const res = await page.request.post(
        `${baseURL()}/drawerPermissions/groups`,
        { form: { type: "User", label: "Nope" } }
      );
      expect(res.status()).toBe(403);
    });
  });

  test.describe("authenticated reads", () => {
    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test("GET /groupTypes lists types with adminOnly flags", async ({
      page,
    }) => {
      const res = await page.request.get(
        `${baseURL()}/drawerPermissions/groupTypes`,
        { headers: { Accept: "application/json" } }
      );
      expect(res.status()).toBe(200);

      const { groupTypes } = (await res.json()) as {
        groupTypes: {
          type: string;
          adminOnly: boolean;
          entryHints: { value: string; label: string }[];
        }[];
      };

      const types = groupTypes.map((g) => g.type);
      expect(types).toEqual(
        expect.arrayContaining(["All", "Authed", "Authed_remote", "User"])
      );

      // population-wide types stay listed for the UI, just flagged adminOnly
      const byType = new Map(groupTypes.map((g) => [g.type, g]));
      expect(byType.get("All")?.adminOnly).toBe(true);
      expect(byType.get("Authed")?.adminOnly).toBe(true);
      expect(byType.get("Authed_remote")?.adminOnly).toBe(true);
      expect(byType.get("User")?.adminOnly).toBe(false);

      for (const groupType of groupTypes) {
        expect(Array.isArray(groupType.entryHints)).toBe(true);
        for (const hint of groupType.entryHints) {
          expect(typeof hint.value).toBe("string");
          expect(typeof hint.label).toBe("string");
        }
      }
    });

    test("GET /groups returns groups flagged with is_personal", async ({
      page,
    }) => {
      const res = await page.request.get(
        `${baseURL()}/drawerPermissions/groups`,
        { headers: { Accept: "application/json" } }
      );
      expect(res.status()).toBe(200);

      const { groups } = (await res.json()) as {
        groups: { is_personal: boolean }[];
      };
      expect(Array.isArray(groups)).toBe(true);
      for (const group of groups) {
        expect(typeof group.is_personal).toBe("boolean");
      }
    });

    test("GET /drawers returns a drawers array", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/drawerPermissions/drawers`,
        { headers: { Accept: "application/json" } }
      );
      expect(res.status()).toBe(200);
      expect(Array.isArray((await res.json()).drawers)).toBe(true);
    });

    test("unsupported verb on /groups returns 405", async ({ page }) => {
      const res = await page.request.delete(
        `${baseURL()}/drawerPermissions/groups`,
        { headers: { Accept: "application/json" } }
      );
      expect(res.status()).toBe(405);
    });

    test("returns 400 for a non-numeric id", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/drawerPermissions/groups/not-a-number`,
        { headers: { Accept: "application/json" } }
      );
      expect(res.status()).toBe(400);
    });

    test("returns 404 for a missing group", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/drawerPermissions/groups/99999999`,
        { headers: { Accept: "application/json" } }
      );
      expect(res.status()).toBe(404);
    });
  });

  test.describe("POST /groups (create)", () => {
    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test("creates a Specific People group", async ({ page }) => {
      const label = uniqueLabel("People");
      const res = await page.request.post(
        `${baseURL()}/drawerPermissions/groups`,
        { form: { type: "User", label } }
      );
      expect(res.status()).toBe(201);

      const { group } = await res.json();
      expect(group.type).toBe("User");
      expect(group.label).toBe(label);
      expect(group.entries_count).toBe(0);
    });

    test("a created group appears in the list, not personal", async ({
      page,
    }) => {
      const label = uniqueLabel("Findable");
      await createDrawerGroup(page, { type: "User", label });

      const res = await page.request.get(
        `${baseURL()}/drawerPermissions/groups`,
        { headers: { Accept: "application/json" } }
      );
      const { groups } = (await res.json()) as {
        groups: { label: string; is_personal: boolean }[];
      };
      const created = groups.find((g) => g.label === label);
      expect(created).toBeTruthy();
      expect(created?.is_personal).toBe(false);
    });

    test("rejects a missing type", async ({ page }) => {
      const res = await page.request.post(
        `${baseURL()}/drawerPermissions/groups`,
        { form: { label: "No type" } }
      );
      expect(res.status()).toBe(422);
      expect((await res.json()).errors).toHaveProperty("type");
    });

    test("rejects an unknown type", async ({ page }) => {
      const res = await page.request.post(
        `${baseURL()}/drawerPermissions/groups`,
        { form: { type: "Nonsense", label: "Bad type" } }
      );
      expect(res.status()).toBe(422);
      expect((await res.json()).errors).toHaveProperty("type");
    });

    test("rejects a missing label", async ({ page }) => {
      const res = await page.request.post(
        `${baseURL()}/drawerPermissions/groups`,
        { form: { type: "User" } }
      );
      expect(res.status()).toBe(422);
      expect((await res.json()).errors).toHaveProperty("label");
    });

    test("an instance admin may use an admin-only type", async ({ page }) => {
      const res = await page.request.post(
        `${baseURL()}/drawerPermissions/groups`,
        { form: { type: "All", label: uniqueLabel("Everyone") } }
      );
      expect(res.status()).toBe(201);
    });
  });

  test.describe("as a non-admin drawer manager", () => {
    test.beforeAll(async ({ browser }) => {
      await ensureDrawerManagerUser(browser);
    });

    test.beforeEach(async ({ page }) => {
      await loginUser(page, DRAWER_MANAGER.username, DRAWER_MANAGER.password);
    });

    test("may create a Specific People group", async ({ page }) => {
      const res = await page.request.post(
        `${baseURL()}/drawerPermissions/groups`,
        { form: { type: "User", label: uniqueLabel("My people") } }
      );
      expect(res.status()).toBe(201);
    });

    test("may not create a group with an admin-only type", async ({ page }) => {
      const res = await page.request.post(
        `${baseURL()}/drawerPermissions/groups`,
        { form: { type: "All", label: uniqueLabel("Everyone") } }
      );
      expect(res.status()).toBe(403);
    });

    test("cannot move a group onto an admin-only type", async ({ page }) => {
      const label = uniqueLabel("Switcher");
      const group = await createDrawerGroup(page, { type: "User", label });

      const res = await page.request.patch(
        `${baseURL()}/drawerPermissions/groups/${group.id}`,
        { form: { type: "Authed", label } }
      );
      expect(res.status()).toBe(200);

      const { group: updated } = await res.json();
      expect(updated.type).toBe("User");
    });

    test("cannot see or mutate another user's group", async ({
      browser,
      page,
    }) => {
      const adminContext = await browser.newContext();
      const adminPage = await adminContext.newPage();
      await loginAdmin(adminPage);
      const group = await createDrawerGroup(adminPage, {
        type: "User",
        label: uniqueLabel("Admin's own"),
      });
      await adminContext.close();

      const show = await page.request.get(
        `${baseURL()}/drawerPermissions/groups/${group.id}`,
        { headers: { Accept: "application/json" } }
      );
      expect(show.status()).toBe(404);

      const update = await page.request.patch(
        `${baseURL()}/drawerPermissions/groups/${group.id}`,
        { form: { type: "User", label: "Hijacked" } }
      );
      expect(update.status()).toBe(404);

      const del = await page.request.delete(
        `${baseURL()}/drawerPermissions/groups/${group.id}`,
        { headers: { Accept: "application/json" } }
      );
      expect(del.status()).toBe(404);
    });
  });

  test.describe("GET /userAutocomplete", () => {
    // exercised as a non-admin manager, who this endpoint exists for
    test.beforeAll(async ({ browser }) => {
      await ensureDrawerManagerUser(browser);
    });

    test.beforeEach(async ({ page }) => {
      await loginUser(page, DRAWER_MANAGER.username, DRAWER_MANAGER.password);
    });

    test("returns matches for a known user", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/drawerPermissions/userAutocomplete?q=admin`,
        { headers: { Accept: "application/json" } }
      );
      expect(res.status()).toBe(200);

      const { matches } = await res.json();
      expect(Array.isArray(matches)).toBe(true);
      const admin = matches.find(
        (m: { username: string }) => m.username === "admin"
      );
      expect(admin).toBeTruthy();
      expect(admin).toHaveProperty("name");
      expect(admin).toHaveProperty("email");
      expect(typeof admin.localUserId).toBe("number");
    });

    test("ignores a query shorter than two characters", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/drawerPermissions/userAutocomplete?q=a`,
        { headers: { Accept: "application/json" } }
      );
      expect(res.status()).toBe(200);
      expect((await res.json()).matches).toEqual([]);
    });

    test("rejects a non-GET verb with 405", async ({ page }) => {
      const res = await page.request.post(
        `${baseURL()}/drawerPermissions/userAutocomplete`,
        { form: { q: "admin" } }
      );
      expect(res.status()).toBe(405);
    });
  });

  test.describe("PATCH/PUT /groups/{id} (update)", () => {
    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test("renames a group", async ({ page }) => {
      const group = await createDrawerGroup(page, {
        type: "User",
        label: uniqueLabel("Before"),
      });

      const after = uniqueLabel("After");
      const res = await page.request.patch(
        `${baseURL()}/drawerPermissions/groups/${group.id}`,
        { form: { label: after } }
      );
      expect(res.status()).toBe(200);

      const { group: updated } = await res.json();
      expect(updated.label).toBe(after);
      expect(updated.type).toBe("User");
    });

    test("ignores a type in the body, keeping members", async ({ page }) => {
      const label = uniqueLabel("Movers");
      const group = await createDrawerGroup(page, { type: "User", label });
      const admin = await findUserByUsername(page, "admin");
      await page.request.post(
        `${baseURL()}/drawerPermissions/groups/${group.id}/members`,
        { form: { localUserId: String(admin.localUserId) } }
      );

      const res = await page.request.put(
        `${baseURL()}/drawerPermissions/groups/${group.id}`,
        { form: { type: "Authed", label } }
      );
      expect(res.status()).toBe(200);

      const { group: updated } = await res.json();
      expect(updated.type).toBe("User");
      expect(updated.entries_count).toBe(1);
    });

    test("returns 404 for a missing group", async ({ page }) => {
      const res = await page.request.patch(
        `${baseURL()}/drawerPermissions/groups/99999999`,
        { form: { label: "Nope" } }
      );
      expect(res.status()).toBe(404);
    });

    test("rejects an invalid label with 422", async ({ page }) => {
      const group = await createDrawerGroup(page, {
        type: "User",
        label: uniqueLabel("Valid"),
      });

      const res = await page.request.patch(
        `${baseURL()}/drawerPermissions/groups/${group.id}`,
        { form: { label: "bad <script>" } }
      );
      expect(res.status()).toBe(422);
      expect((await res.json()).errors).toHaveProperty("label");
    });
  });

  test.describe("DELETE /groups/{id} (delete)", () => {
    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test("removes a group, which is then gone", async ({ page }) => {
      const group = await createDrawerGroup(page, {
        type: "User",
        label: uniqueLabel("Doomed"),
      });

      const del = await page.request.delete(
        `${baseURL()}/drawerPermissions/groups/${group.id}`,
        { headers: { Accept: "application/json" } }
      );
      expect(del.status()).toBe(200);
      expect((await del.json()).deleted).toBe(group.id);

      const show = await page.request.get(
        `${baseURL()}/drawerPermissions/groups/${group.id}`,
        { headers: { Accept: "application/json" } }
      );
      expect(show.status()).toBe(404);
    });

    test("returns 404 for a missing group", async ({ page }) => {
      const res = await page.request.delete(
        `${baseURL()}/drawerPermissions/groups/99999999`,
        { headers: { Accept: "application/json" } }
      );
      expect(res.status()).toBe(404);
    });
  });

  test.describe("group members", () => {
    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test("adds, lists, and removes a member", async ({ page }) => {
      const group = await createDrawerGroup(page, {
        type: "User",
        label: uniqueLabel("People"),
      });
      const admin = await findUserByUsername(page, "admin");

      const add = await page.request.post(
        `${baseURL()}/drawerPermissions/groups/${group.id}/members`,
        { form: { localUserId: String(admin.localUserId) } }
      );
      expect(add.status()).toBe(201);
      expect((await add.json()).member.userId).toBe(admin.localUserId);

      const list = await page.request.get(
        `${baseURL()}/drawerPermissions/groups/${group.id}/members`,
        { headers: { Accept: "application/json" } }
      );
      expect(list.status()).toBe(200);
      const { members } = await list.json();
      expect(
        members.some((m: { userId: number }) => m.userId === admin.localUserId)
      ).toBe(true);

      const remove = await page.request.delete(
        `${baseURL()}/drawerPermissions/groups/${group.id}/members/${
          admin.localUserId
        }`,
        { headers: { Accept: "application/json" } }
      );
      expect(remove.status()).toBe(200);
      expect((await remove.json()).removed).toBe(admin.localUserId);
    });

    test("rejects a duplicate member with 409", async ({ page }) => {
      const group = await createDrawerGroup(page, {
        type: "User",
        label: uniqueLabel("Dupes"),
      });
      const admin = await findUserByUsername(page, "admin");

      const first = await page.request.post(
        `${baseURL()}/drawerPermissions/groups/${group.id}/members`,
        { form: { localUserId: String(admin.localUserId) } }
      );
      expect(first.status()).toBe(201);

      const second = await page.request.post(
        `${baseURL()}/drawerPermissions/groups/${group.id}/members`,
        { form: { localUserId: String(admin.localUserId) } }
      );
      expect(second.status()).toBe(409);
    });

    test("requires exactly one of localUserId or remoteUserId", async ({
      page,
    }) => {
      const group = await createDrawerGroup(page, {
        type: "User",
        label: uniqueLabel("OneOf"),
      });

      const res = await page.request.post(
        `${baseURL()}/drawerPermissions/groups/${group.id}/members`,
        { form: {} }
      );
      expect(res.status()).toBe(422);
    });

    test("only User groups take members", async ({ page }) => {
      const group = await createDrawerGroup(page, {
        type: "All",
        label: uniqueLabel("NoMembers"),
      });

      const res = await page.request.post(
        `${baseURL()}/drawerPermissions/groups/${group.id}/members`,
        { form: { localUserId: "1" } }
      );
      expect(res.status()).toBe(422);
    });

    test("removing a non-member returns 404", async ({ page }) => {
      const group = await createDrawerGroup(page, {
        type: "User",
        label: uniqueLabel("Empty"),
      });

      const res = await page.request.delete(
        `${baseURL()}/drawerPermissions/groups/${group.id}/members/99999999`,
        { headers: { Accept: "application/json" } }
      );
      expect(res.status()).toBe(404);
    });

    test("listing members of a missing group returns 404", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/drawerPermissions/groups/99999999/members`,
        { headers: { Accept: "application/json" } }
      );
      expect(res.status()).toBe(404);
    });
  });

  test.describe("group entries", () => {
    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test("adds, lists, updates, and removes an entry", async ({ page }) => {
      const type = await findAuthHelperGroupType(page);
      if (type === null) {
        test.skip(true, "the instance's auth helper defines no group types");
        return;
      }

      const group = await createDrawerGroup(page, {
        type,
        label: uniqueLabel("Attributes"),
      });

      const add = await page.request.post(
        `${baseURL()}/drawerPermissions/groups/${group.id}/entries`,
        { form: { value: "CSCI.1001" } }
      );
      expect(add.status()).toBe(201);
      const { entry } = (await add.json()) as { entry: GroupEntry };
      expect(entry.value).toBe("CSCI.1001");

      const list = await page.request.get(
        `${baseURL()}/drawerPermissions/groups/${group.id}/entries`,
        { headers: { Accept: "application/json" } }
      );
      expect(list.status()).toBe(200);
      const { entries } = (await list.json()) as { entries: GroupEntry[] };
      expect(entries.some((e) => e.id === entry.id)).toBe(true);

      const update = await page.request.put(
        `${baseURL()}/drawerPermissions/groups/${group.id}/entries/${entry.id}`,
        { form: { value: "CSCI.2001" } }
      );
      expect(update.status()).toBe(200);
      const updated = (await update.json()) as { entry: GroupEntry };
      expect(updated.entry.value).toBe("CSCI.2001");

      const remove = await page.request.delete(
        `${baseURL()}/drawerPermissions/groups/${group.id}/entries/${entry.id}`,
        { headers: { Accept: "application/json" } }
      );
      expect(remove.status()).toBe(200);
      expect((await remove.json()).removed).toBe(entry.id);
    });

    test("only auth-helper group types take entries", async ({ page }) => {
      const group = await createDrawerGroup(page, {
        type: "User",
        label: uniqueLabel("NoEntries"),
      });

      const res = await page.request.post(
        `${baseURL()}/drawerPermissions/groups/${group.id}/entries`,
        { form: { value: "anything" } }
      );
      expect(res.status()).toBe(422);

      // The read side too: User groups store membership in the same
      // group_values rows, so listing must not serve member ids as entries.
      const list = await page.request.get(
        `${baseURL()}/drawerPermissions/groups/${group.id}/entries`,
        { headers: { Accept: "application/json" } }
      );
      expect(list.status()).toBe(422);
    });

    test("updating or removing a missing entry returns 404", async ({
      page,
    }) => {
      const type = await findAuthHelperGroupType(page);
      if (type === null) {
        test.skip(true, "the instance's auth helper defines no group types");
        return;
      }

      const group = await createDrawerGroup(page, {
        type,
        label: uniqueLabel("NoSuchEntry"),
      });

      const update = await page.request.put(
        `${baseURL()}/drawerPermissions/groups/${group.id}/entries/99999999`,
        { form: { value: "anything" } }
      );
      expect(update.status()).toBe(404);

      const remove = await page.request.delete(
        `${baseURL()}/drawerPermissions/groups/${group.id}/entries/99999999`,
        { headers: { Accept: "application/json" } }
      );
      expect(remove.status()).toBe(404);
    });

    test("listing entries of a missing group returns 404", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/drawerPermissions/groups/99999999/entries`,
        { headers: { Accept: "application/json" } }
      );
      expect(res.status()).toBe(404);
    });
  });
});
