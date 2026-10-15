import { test, expect, type Browser, type Page } from "@playwright/test";
import { execSync } from "child_process";
import path from "path";
import {
  loginUser,
  createUser,
  createCollection,
  refreshDatabase,
  baseURL,
} from "../helpers";

const projectRoot = path.resolve(__dirname, "../..");

const PERM_NOPERM = 0;
const PERM_CREATEDRAWERS = 30;
const PERM_ORIGINALS = 40;
const PERM_ADMIN = 60;

type GrantTable =
  "instance_permissions" | "collection_permissions" | "drawer_permissions";

function queryDb(sql: string): string {
  return execSync(
    `docker compose exec -T postgres psql -U elevator -d elevator -tA -c "${sql}"`,
    { cwd: projectRoot, encoding: "utf8" },
  ).trim();
}

function grantLevelId(table: GrantTable, grantId: number): string {
  return queryDb(`SELECT permission_id FROM ${table} WHERE id = ${grantId}`);
}

function instanceId(): string {
  return queryDb("SELECT id FROM instances ORDER BY id LIMIT 1");
}

function userIdInDb(username: string): string {
  const id = queryDb(`SELECT id FROM users WHERE username = '${username}'`);
  expect(id, `user ${username} should exist`).not.toBe("");
  return id;
}

async function newLoggedInPage(
  browser: Browser,
  username: string,
  password: string,
): Promise<Page> {
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();
  await loginUser(page, username, password);
  return page;
}

async function newAnonymousPage(browser: Browser): Promise<Page> {
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  return context.newPage();
}

async function permissionLevelId(admin: Page, level: number): Promise<number> {
  const res = await admin.request.get(
    `${baseURL()}/adminPermissions/permissionLevels`,
    { headers: { Accept: "application/json" } },
  );
  const { permissionLevels } = (await res.json()) as {
    permissionLevels: { id: number; level: number }[];
  };
  const match = permissionLevels.find((l) => Number(l.level) === level);
  expect(match, `seed should include permission level ${level}`).toBeDefined();
  return match!.id;
}

async function createGroup(admin: Page, label: string): Promise<number> {
  const res = await admin.request.post(`${baseURL()}/adminPermissions/groups`, {
    form: { type: "User", label },
  });
  expect(res.status()).toBe(201);
  return (await res.json()).group.id as number;
}

async function createInstanceGrant(
  admin: Page,
  groupId: number,
  levelId: number,
): Promise<number> {
  const res = await admin.request.post(
    `${baseURL()}/adminPermissions/instanceGrants`,
    { form: { groupId: String(groupId), permissionLevelId: String(levelId) } },
  );
  expect(res.status()).toBe(201);
  return (await res.json()).instanceGrant.id as number;
}

async function createCollectionGrant(
  admin: Page,
  collectionId: number,
  groupId: number,
  levelId: number,
): Promise<number> {
  const res = await admin.request.post(
    `${baseURL()}/adminPermissions/collectionGrants`,
    {
      form: {
        collectionId: String(collectionId),
        groupId: String(groupId),
        permissionLevelId: String(levelId),
      },
    },
  );
  expect(res.status()).toBe(201);
  return (await res.json()).collectionGrant.id as number;
}

async function newPageWithInstanceLevel(
  browser: Browser,
  admin: Page,
  username: string,
  password: string,
  level: number,
): Promise<Page> {
  await createUser(admin, username, password, { isSuperAdmin: false });
  const groupId = await createGroup(admin, `Level ${level} ${username}`);
  const memberResponse = await admin.request.post(
    `${baseURL()}/adminPermissions/groups/${groupId}/members`,
    { form: { localUserId: userIdInDb(username) } },
  );
  expect(memberResponse.status()).toBe(201);
  const levelId = await permissionLevelId(admin, level);
  await createInstanceGrant(admin, groupId, levelId);
  return newLoggedInPage(browser, username, password);
}

async function updateGrant(
  page: Page,
  fields: {
    permissionType: string;
    permissionTypeId?: string | number;
    grantId: number;
    levelId: number;
  },
) {
  const form: Record<string, string> = {
    permissionType: fields.permissionType,
    [`permission[${fields.grantId}]`]: String(fields.levelId),
  };
  if (fields.permissionTypeId !== undefined) {
    form.permissionTypeId = String(fields.permissionTypeId);
  }
  return postUpdateForm(page, form);
}

function postUpdateForm(page: Page, form: Record<string, string>) {
  return page.request.post(`${baseURL()}/permissions/update`, {
    form,
    // Following the redirect reports the edit page's status
    // (a 500 on the seed data), not the update's response.
    maxRedirects: 0,
  });
}

async function setUp(browser: Browser) {
  const admin = await newLoggedInPage(browser, "admin", "admin");
  // Grants start at noPermId, or the authorization tests
  // pass even when update() skips its permission check.
  const noPermId = await permissionLevelId(admin, PERM_NOPERM);
  const adminLevelId = await permissionLevelId(admin, PERM_ADMIN);
  const groupId = await createGroup(admin, `Target ${Date.now()}`);
  const collectionId = await createCollection(admin, "Grant Target");
  const instanceGrantId = await createInstanceGrant(admin, groupId, noPermId);
  const collectionGrantId = await createCollectionGrant(
    admin,
    collectionId,
    groupId,
    noPermId,
  );
  return {
    admin,
    noPermId,
    adminLevelId,
    collectionId,
    instanceGrantId,
    collectionGrantId,
  };
}

test.describe("Permissions::update authorization", () => {
  test.beforeEach(() => {
    refreshDatabase();
  });

  test("an anonymous caller cannot raise an instance grant with a capitalised type", async ({
    browser,
  }) => {
    const { noPermId, adminLevelId, instanceGrantId } = await setUp(browser);
    const page = await newAnonymousPage(browser);

    await updateGrant(page, {
      permissionType: "Instance",
      permissionTypeId: instanceId(),
      grantId: instanceGrantId,
      levelId: adminLevelId,
    });

    expect(grantLevelId("instance_permissions", instanceGrantId)).toBe(
      String(noPermId),
    );
  });

  test("an anonymous caller cannot raise a collection grant with a capitalised type", async ({
    browser,
  }) => {
    const { noPermId, adminLevelId, collectionId, collectionGrantId } =
      await setUp(browser);
    const page = await newAnonymousPage(browser);

    await updateGrant(page, {
      permissionType: "Collection",
      permissionTypeId: collectionId,
      grantId: collectionGrantId,
      levelId: adminLevelId,
    });

    expect(grantLevelId("collection_permissions", collectionGrantId)).toBe(
      String(noPermId),
    );
  });

  test("an instance admin cannot change grants on a collection outside the instance", async ({
    browser,
  }) => {
    const { admin, noPermId, adminLevelId, collectionId, collectionGrantId } =
      await setUp(browser);
    const ian = await newPageWithInstanceLevel(
      browser,
      admin,
      `ian_${Date.now()}`,
      "ian-pw",
      PERM_ADMIN,
    );
    queryDb(
      `DELETE FROM instance_collection WHERE collection_id = ${collectionId}`,
    );

    await updateGrant(ian, {
      permissionType: "collection",
      permissionTypeId: collectionId,
      grantId: collectionGrantId,
      levelId: adminLevelId,
    });

    expect(grantLevelId("collection_permissions", collectionGrantId)).toBe(
      String(noPermId),
    );
  });

  test("a signed-in user without grants cannot raise an instance grant", async ({
    browser,
  }) => {
    const { admin, noPermId, adminLevelId, instanceGrantId } =
      await setUp(browser);
    const mallory = `mallory_${Date.now()}`;
    await createUser(admin, mallory, "mallory-pw", { isSuperAdmin: false });
    const malloryPage = await newLoggedInPage(browser, mallory, "mallory-pw");

    await updateGrant(malloryPage, {
      permissionType: "instance",
      grantId: instanceGrantId,
      levelId: adminLevelId,
    });

    expect(grantLevelId("instance_permissions", instanceGrantId)).toBe(
      String(noPermId),
    );
  });

  test("an admin can still change an instance grant", async ({ browser }) => {
    const { admin, adminLevelId, instanceGrantId } = await setUp(browser);

    const res = await updateGrant(admin, {
      permissionType: "instance",
      grantId: instanceGrantId,
      levelId: adminLevelId,
    });

    expect(res.status()).toBe(303);
    expect(res.headers()["location"]).toContain("/permissions/edit/instance");
    expect(grantLevelId("instance_permissions", instanceGrantId)).toBe(
      String(adminLevelId),
    );
  });

  test("an admin can still change a collection grant", async ({ browser }) => {
    const { admin, adminLevelId, collectionId, collectionGrantId } =
      await setUp(browser);

    await updateGrant(admin, {
      permissionType: "collection",
      permissionTypeId: collectionId,
      grantId: collectionGrantId,
      levelId: adminLevelId,
    });

    expect(grantLevelId("collection_permissions", collectionGrantId)).toBe(
      String(adminLevelId),
    );
  });
});

test.describe("Permissions::update input validation", () => {
  test.beforeEach(() => {
    refreshDatabase();
  });

  test("an unknown permission level is rejected and the grant is unchanged", async ({
    browser,
  }) => {
    const { admin, noPermId, instanceGrantId } = await setUp(browser);

    const res = await updateGrant(admin, {
      permissionType: "instance",
      grantId: instanceGrantId,
      levelId: 999999,
    });

    expect(res.status()).toBe(422);
    expect(grantLevelId("instance_permissions", instanceGrantId)).toBe(
      String(noPermId),
    );
  });

  test("a permission level that is not an integer is rejected", async ({
    browser,
  }) => {
    const { admin, noPermId, instanceGrantId } = await setUp(browser);

    const res = await postUpdateForm(admin, {
      permissionType: "instance",
      [`permission[${instanceGrantId}]`]: "admin",
    });

    expect(res.status()).toBe(422);
    expect(grantLevelId("instance_permissions", instanceGrantId)).toBe(
      String(noPermId),
    );
  });

  test("a grant key that is not an integer is rejected", async ({
    browser,
  }) => {
    const { admin, adminLevelId } = await setUp(browser);

    const res = await postUpdateForm(admin, {
      permissionType: "instance",
      "permission[abc]": String(adminLevelId),
    });

    expect(res.status()).toBe(422);
  });

  test("a collection id that is not an integer is rejected", async ({
    browser,
  }) => {
    const { admin, noPermId, adminLevelId, collectionId, collectionGrantId } =
      await setUp(browser);

    const res = await updateGrant(admin, {
      permissionType: "collection",
      permissionTypeId: `${collectionId}abc`,
      grantId: collectionGrantId,
      levelId: adminLevelId,
    });

    expect(res.status()).toBe(422);
    expect(grantLevelId("collection_permissions", collectionGrantId)).toBe(
      String(noPermId),
    );
  });
});

test.describe("Permissions::update drawer grants", () => {
  test.beforeEach(() => {
    refreshDatabase();
  });

  async function setUpDrawerOwner(browser: Browser) {
    const admin = await newLoggedInPage(browser, "admin", "admin");
    const ownerPage = await newPageWithInstanceLevel(
      browser,
      admin,
      `owner_${Date.now()}`,
      "owner-pw",
      PERM_CREATEDRAWERS,
    );
    const res = await ownerPage.request.post(`${baseURL()}/drawers/addDrawer`, {
      headers: { Accept: "application/json" },
      form: { drawerTitle: "Owner's Drawer" },
    });
    expect(res.status()).toBe(200);
    const drawerId = (await res.json()).drawerId as number;
    const ownerGrantId = Number(
      queryDb(
        `SELECT id FROM drawer_permissions WHERE drawer_id = ${drawerId}`,
      ),
    );
    return { admin, ownerPage, drawerId, ownerGrantId };
  }

  test("a drawer owner cannot raise a drawer grant above originals", async ({
    browser,
  }) => {
    const { admin, ownerPage, drawerId, ownerGrantId } =
      await setUpDrawerOwner(browser);
    const levelBefore = grantLevelId("drawer_permissions", ownerGrantId);

    const res = await updateGrant(ownerPage, {
      permissionType: "drawer",
      permissionTypeId: drawerId,
      grantId: ownerGrantId,
      levelId: await permissionLevelId(admin, PERM_ADMIN),
    });

    expect(res.status()).toBe(422);
    expect(grantLevelId("drawer_permissions", ownerGrantId)).toBe(levelBefore);
  });

  test("a drawer owner can set a drawer grant to originals", async ({
    browser,
  }) => {
    const { admin, ownerPage, drawerId, ownerGrantId } =
      await setUpDrawerOwner(browser);
    const originalsLevelId = await permissionLevelId(admin, PERM_ORIGINALS);

    const res = await updateGrant(ownerPage, {
      permissionType: "drawer",
      permissionTypeId: drawerId,
      grantId: ownerGrantId,
      levelId: originalsLevelId,
    });

    expect(res.status()).toBe(303);
    expect(grantLevelId("drawer_permissions", ownerGrantId)).toBe(
      String(originalsLevelId),
    );
  });
});
