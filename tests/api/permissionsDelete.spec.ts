import {
  test,
  expect,
  type APIResponse,
  type Browser,
  type Page,
} from "@playwright/test";
import {
  createCollection,
  createUser,
  newInstanceAdminContext,
  newLoggedInContext,
  queryDb,
  refreshDatabase,
  userIdInDb,
  baseURL,
} from "../helpers";

const PERM_ADMIN = 60;
const PERM_CREATEDRAWERS = 30;

type GrantTable =
  "instance_permissions" | "collection_permissions" | "drawer_permissions";

function grantExists(table: GrantTable, grantId: number): boolean {
  return queryDb(`SELECT count(*) FROM ${table} WHERE id = ${grantId}`) === "1";
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

async function newDrawerCreatorPage(
  browser: Browser,
  admin: Page,
  username: string,
): Promise<Page> {
  const password = `${username}-pw`;
  await createUser(admin, username, password, { isSuperAdmin: false });
  const groupId = await createGroup(admin, `Drawer creators ${username}`);
  const memberResponse = await admin.request.post(
    `${baseURL()}/adminPermissions/groups/${groupId}/members`,
    { form: { localUserId: userIdInDb(username) } },
  );
  expect(memberResponse.status()).toBe(201);
  const levelId = await permissionLevelId(admin, PERM_CREATEDRAWERS);
  await createInstanceGrant(admin, groupId, levelId);
  return newLoggedInContext(browser, username, password);
}

async function createDrawerWithOwnerGrant(owner: Page, title: string) {
  const res = await owner.request.post(`${baseURL()}/drawers/addDrawer`, {
    headers: { Accept: "application/json" },
    form: { drawerTitle: title },
  });
  expect(res.status()).toBe(200);
  const drawerId = (await res.json()).drawerId as number;
  const grantId = Number(
    queryDb(`SELECT id FROM drawer_permissions WHERE drawer_id = ${drawerId}`),
  );
  return { drawerId, grantId };
}

function insertInstance(name: string): string {
  // A bare INSERT ... RETURNING makes psql also print
  // "INSERT 0 1", which would end up in the returned id.
  return queryDb(
    `WITH inserted AS (INSERT INTO instances (name) VALUES ('${name}') RETURNING id) SELECT id FROM inserted`,
  );
}

function deleteGrant(
  page: Page,
  permissionType: string,
  grantId: number | string,
  permissionTypeId: string | number = "",
) {
  return page.request.delete(
    `${baseURL()}/permissions/delete/${permissionType}/${grantId}/${permissionTypeId}`,
    // Keep maxRedirects at 0: the tests assert the status
    // and Location header from Permissions::delete itself.
    { maxRedirects: 0 },
  );
}

function expectNoPermissionRedirect(res: APIResponse) {
  expect(res.status()).toBe(303);
  expect(res.headers()["location"]).toContain(
    "/errorHandler/error/noPermission",
  );
}

async function createInstanceAndCollectionGrants(browser: Browser) {
  const admin = await newLoggedInContext(browser, "admin", "admin");
  const adminLevelId = await permissionLevelId(admin, PERM_ADMIN);
  const groupId = await createGroup(admin, `Target ${Date.now()}`);
  const collectionId = await createCollection(admin, "Grant Target");
  const instanceGrantId = await createInstanceGrant(
    admin,
    groupId,
    adminLevelId,
  );
  const collectionGrantId = await createCollectionGrant(
    admin,
    collectionId,
    groupId,
    adminLevelId,
  );
  return { admin, collectionId, instanceGrantId, collectionGrantId };
}

test.describe("Permissions::delete authorization", () => {
  test.beforeEach(() => {
    refreshDatabase();
  });

  test("an anonymous caller cannot delete an instance grant with a capitalised type", async ({
    browser,
  }) => {
    const { instanceGrantId } =
      await createInstanceAndCollectionGrants(browser);
    const page = await newAnonymousPage(browser);

    const res = await deleteGrant(page, "Instance", instanceGrantId);

    expect(res.status()).toBe(422);
    expect(grantExists("instance_permissions", instanceGrantId)).toBe(true);
  });

  test("an anonymous caller cannot delete a collection grant with a capitalised type", async ({
    browser,
  }) => {
    const { collectionId, collectionGrantId } =
      await createInstanceAndCollectionGrants(browser);
    const page = await newAnonymousPage(browser);

    const res = await deleteGrant(
      page,
      "Collection",
      collectionGrantId,
      collectionId,
    );

    expect(res.status()).toBe(422);
    expect(grantExists("collection_permissions", collectionGrantId)).toBe(true);
  });

  test("an anonymous caller cannot delete an instance grant", async ({
    browser,
  }) => {
    const { instanceGrantId } =
      await createInstanceAndCollectionGrants(browser);
    const page = await newAnonymousPage(browser);

    const res = await deleteGrant(page, "instance", instanceGrantId);

    expectNoPermissionRedirect(res);
    expect(grantExists("instance_permissions", instanceGrantId)).toBe(true);
  });

  test("an instance admin cannot delete a grant on a collection outside the instance", async ({
    browser,
  }) => {
    const { admin, collectionId, collectionGrantId } =
      await createInstanceAndCollectionGrants(browser);
    const instanceAdmin = await newInstanceAdminContext(
      browser,
      admin,
      `instance_admin_${Date.now()}`,
      "instance-admin-pw",
    );
    queryDb(
      `DELETE FROM instance_collection WHERE collection_id = ${collectionId}`,
    );

    const res = await deleteGrant(
      instanceAdmin,
      "collection",
      collectionGrantId,
      collectionId,
    );

    expect(res.status()).toBe(404);
    expect(grantExists("collection_permissions", collectionGrantId)).toBe(true);
  });

  test("an instance admin cannot delete an instance grant from another instance", async ({
    browser,
  }) => {
    const { admin, instanceGrantId } =
      await createInstanceAndCollectionGrants(browser);
    const instanceAdmin = await newInstanceAdminContext(
      browser,
      admin,
      `instance_admin_${Date.now()}`,
      "instance-admin-pw",
    );
    const otherInstanceId = insertInstance("Other Instance");
    queryDb(
      `UPDATE instance_permissions SET instance_id = ${otherInstanceId} WHERE id = ${instanceGrantId}`,
    );

    try {
      const res = await deleteGrant(instanceAdmin, "instance", instanceGrantId);

      expect(res.status()).toBe(404);
      expect(grantExists("instance_permissions", instanceGrantId)).toBe(true);
    } finally {
      // reset-test-db.sh doesn't truncate instances, so
      // without these DELETEs each run leaves an
      // "Other Instance" row.
      queryDb(
        `DELETE FROM instance_permissions WHERE instance_id = ${otherInstanceId}`,
      );
      queryDb(`DELETE FROM instances WHERE id = ${otherInstanceId}`);
    }
  });

  test("a drawer owner cannot delete a grant on someone else's drawer", async ({
    browser,
  }) => {
    const admin = await newLoggedInContext(browser, "admin", "admin");
    const alice = await newDrawerCreatorPage(
      browser,
      admin,
      `alice_${Date.now()}`,
    );
    const bob = await newDrawerCreatorPage(browser, admin, `bob_${Date.now()}`);
    const alicesDrawer = await createDrawerWithOwnerGrant(
      alice,
      "Alice's Drawer",
    );
    const bobsDrawer = await createDrawerWithOwnerGrant(bob, "Bob's Drawer");

    const res = await deleteGrant(
      alice,
      "drawer",
      bobsDrawer.grantId,
      alicesDrawer.drawerId,
    );

    expectNoPermissionRedirect(res);
    expect(grantExists("drawer_permissions", bobsDrawer.grantId)).toBe(true);
  });

  test("a signed-in user without grants cannot delete an instance grant", async ({
    browser,
  }) => {
    const { admin, instanceGrantId } =
      await createInstanceAndCollectionGrants(browser);
    const username = `no_grants_${Date.now()}`;
    await createUser(admin, username, "no-grants-pw", { isSuperAdmin: false });
    const userWithoutGrants = await newLoggedInContext(
      browser,
      username,
      "no-grants-pw",
    );

    const res = await deleteGrant(
      userWithoutGrants,
      "instance",
      instanceGrantId,
    );

    expectNoPermissionRedirect(res);
    expect(grantExists("instance_permissions", instanceGrantId)).toBe(true);
  });

  test("an admin can still delete an instance grant", async ({ browser }) => {
    const { admin, instanceGrantId } =
      await createInstanceAndCollectionGrants(browser);

    const res = await deleteGrant(admin, "instance", instanceGrantId);

    expect(res.status()).toBe(303);
    expect(res.headers()["location"]).toContain("/permissions/edit/instance");
    expect(grantExists("instance_permissions", instanceGrantId)).toBe(false);
  });

  test("an admin can still delete a collection grant", async ({ browser }) => {
    const { admin, collectionId, collectionGrantId } =
      await createInstanceAndCollectionGrants(browser);

    const res = await deleteGrant(
      admin,
      "collection",
      collectionGrantId,
      collectionId,
    );

    expect(res.status()).toBe(303);
    expect(res.headers()["location"]).toContain(
      `/permissions/edit/collection/${collectionId}`,
    );
    expect(grantExists("collection_permissions", collectionGrantId)).toBe(
      false,
    );
  });

  test("a drawer owner can still delete a grant on their own drawer", async ({
    browser,
  }) => {
    const admin = await newLoggedInContext(browser, "admin", "admin");
    const owner = await newDrawerCreatorPage(
      browser,
      admin,
      `owner_${Date.now()}`,
    );
    const drawer = await createDrawerWithOwnerGrant(owner, "Owner's Drawer");

    const res = await deleteGrant(
      owner,
      "drawer",
      drawer.grantId,
      drawer.drawerId,
    );

    expect(res.status()).toBe(303);
    expect(res.headers()["location"]).toContain(
      `/permissions/edit/drawer/${drawer.drawerId}`,
    );
    expect(grantExists("drawer_permissions", drawer.grantId)).toBe(false);
  });
});

test.describe("Permissions::delete request method", () => {
  test.beforeEach(() => {
    refreshDatabase();
  });

  test("an admin following a link (a GET) gets a 405 and the grant is unchanged", async ({
    browser,
  }) => {
    const { admin, instanceGrantId } =
      await createInstanceAndCollectionGrants(browser);

    const res = await admin.request.get(
      `${baseURL()}/permissions/delete/instance/${instanceGrantId}/`,
      { maxRedirects: 0 },
    );

    expect(res.status()).toBe(405);
    expect(res.headers()["allow"]).toBe("DELETE");
    expect(grantExists("instance_permissions", instanceGrantId)).toBe(true);
  });
});

test.describe("Permissions::delete input validation", () => {
  test.beforeEach(() => {
    refreshDatabase();
  });

  test("a grant id that is not an integer gets a 422 and the grant is unchanged", async ({
    browser,
  }) => {
    const { admin, instanceGrantId } =
      await createInstanceAndCollectionGrants(browser);

    const res = await deleteGrant(admin, "instance", `${instanceGrantId}abc`);

    expect(res.status()).toBe(422);
    expect(grantExists("instance_permissions", instanceGrantId)).toBe(true);
  });
  test("the redirect goes to the grant's own edit page, whatever object id the URL names", async ({
    browser,
  }) => {
    const { admin, collectionId, collectionGrantId } =
      await createInstanceAndCollectionGrants(browser);

    const res = await deleteGrant(
      admin,
      "collection",
      collectionGrantId,
      999999,
    );

    expect(res.status()).toBe(303);
    expect(res.headers()["location"]).toMatch(
      new RegExp(`/permissions/edit/collection/${collectionId}$`),
    );
    expect(grantExists("collection_permissions", collectionGrantId)).toBe(
      false,
    );
  });
});
