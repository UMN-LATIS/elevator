import { test, expect, type Browser, type Page } from "@playwright/test";
import { execSync } from "child_process";
import path from "path";
import { loginUser, createUser, refreshDatabase, baseURL } from "../helpers";

const projectRoot = path.resolve(__dirname, "../..");

const PERM_ADMIN = 60;

function queryDb(sql: string): string {
  return execSync(
    `docker compose exec -T postgres psql -U elevator -d elevator -tA -c "${sql}"`,
    { cwd: projectRoot, encoding: "utf8" },
  ).trim();
}

function userColumnInDb(username: string, column: string): string {
  return queryDb(`SELECT ${column} FROM users WHERE username = '${username}'`);
}

function isSuperAdminInDb(username: string): boolean {
  return userColumnInDb(username, "issuperadmin") === "t";
}

function countLocalUsersNamed(username: string): number {
  return Number(
    queryDb(
      `SELECT count(*) FROM users WHERE username = '${username}' AND usertype = 'Local'`,
    ),
  );
}

function userExistsInDb(username: string): boolean {
  return countLocalUsersNamed(username) > 0;
}

function userIdInDb(username: string): string {
  const id = userColumnInDb(username, "id");
  expect(id, `user ${username} should exist`).not.toBe("");
  return id;
}

function displayNameInDb(username: string): string {
  return userColumnInDb(username, "displayname");
}

async function newLoggedInContext(
  browser: Browser,
  username: string,
  password: string,
): Promise<Page> {
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();
  await loginUser(page, username, password);
  return page;
}

async function readOwnUserId(page: Page, username: string): Promise<string> {
  const res = await page.request.get(
    `${baseURL()}/permissions/editUser/${username}`,
  );
  expect(res.ok()).toBe(true);
  const html = await res.text();
  const match = html.match(/name="userId"[^>]*value="(\d+)"/);
  expect(match, "editUser form should expose the user's own id").not.toBeNull();
  return match![1];
}

async function newInstanceAdminContext(
  browser: Browser,
  superAdminPage: Page,
  username: string,
  password: string,
): Promise<Page> {
  await createUser(superAdminPage, username, password, { isSuperAdmin: false });

  const group = await superAdminPage.request.post(
    `${baseURL()}/adminPermissions/groups`,
    { form: { type: "User", label: `Admins ${username}` } },
  );
  expect(group.status()).toBe(201);
  const groupId = (await group.json()).group.id as number;

  const member = await superAdminPage.request.post(
    `${baseURL()}/adminPermissions/groups/${groupId}/members`,
    { form: { localUserId: userIdInDb(username) } },
  );
  expect(member.status()).toBe(201);

  const levels = await superAdminPage.request.get(
    `${baseURL()}/adminPermissions/permissionLevels`,
    { headers: { Accept: "application/json" } },
  );
  const { permissionLevels } = (await levels.json()) as {
    permissionLevels: { id: number; level: number }[];
  };
  const adminLevel = permissionLevels.find((l) => l.level === PERM_ADMIN);
  expect(
    adminLevel,
    "seed should include the admin permission level",
  ).toBeDefined();

  const grant = await superAdminPage.request.post(
    `${baseURL()}/adminPermissions/instanceGrants`,
    {
      form: {
        groupId: String(groupId),
        permissionLevelId: String(adminLevel!.id),
      },
    },
  );
  expect(grant.status()).toBe(201);

  const page = await newLoggedInContext(browser, username, password);
  const adminOnly = await page.request.get(
    `${baseURL()}/adminPermissions/permissionLevels`,
    { headers: { Accept: "application/json" } },
  );
  expect(adminOnly.status(), `${username} should be an instance admin`).toBe(
    200,
  );
  expect(isSuperAdminInDb(username)).toBe(false);
  return page;
}

async function saveUser(page: Page, form: Record<string, string>) {
  return page.request.post(`${baseURL()}/permissions/saveUser`, { form });
}

test.describe("Permissions::saveUser authorization", () => {
  test.beforeEach(() => {
    refreshDatabase();
  });

  test("an anonymous caller cannot create an account or grant SuperAdmin", async ({
    browser,
  }) => {
    const victim = `anon_created_${Date.now()}`;

    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();
    await page.request.post(`${baseURL()}/permissions/saveUser`, {
      form: {
        username: victim,
        password: "victim-pw",
        label: victim,
        email: `${victim}@test.local`,
        isSuperAdmin: "On",
      },
    });

    expect(isSuperAdminInDb(victim)).toBe(false);
    expect(userExistsInDb(victim)).toBe(false);
  });

  test("a non-admin cannot grant themselves SuperAdmin on their own account", async ({
    browser,
  }) => {
    const mallory = `mallory_${Date.now()}`;
    const password = "mallory-pw";

    const admin = await newLoggedInContext(browser, "admin", "admin");
    await createUser(admin, mallory, password, { isSuperAdmin: false });
    expect(isSuperAdminInDb(mallory)).toBe(false);

    const malloryPage = await newLoggedInContext(browser, mallory, password);
    const malloryId = await readOwnUserId(malloryPage, mallory);

    await malloryPage.request.post(`${baseURL()}/permissions/saveUser`, {
      form: {
        userId: malloryId,
        username: mallory,
        password: "dontchangeme",
        label: mallory,
        email: `${mallory}@test.local`,
        isSuperAdmin: "On",
      },
    });

    expect(isSuperAdminInDb(mallory)).toBe(false);
  });

  test("a user can still edit their own display name and email", async ({
    browser,
  }) => {
    const mallory = `mallory_${Date.now()}`;
    const password = "mallory-pw";
    const newName = `Renamed ${Date.now()}`;

    const admin = await newLoggedInContext(browser, "admin", "admin");
    await createUser(admin, mallory, password, { isSuperAdmin: false });

    const malloryPage = await newLoggedInContext(browser, mallory, password);
    const malloryId = await readOwnUserId(malloryPage, mallory);

    const res = await malloryPage.request.post(
      `${baseURL()}/permissions/saveUser`,
      {
        form: {
          userId: malloryId,
          username: mallory,
          password: "dontchangeme",
          label: newName,
          email: `${mallory}-new@test.local`,
        },
      },
    );

    expect(res.ok()).toBe(true);
    expect(displayNameInDb(mallory)).toBe(newName);
    expect(isSuperAdminInDb(mallory)).toBe(false);
  });

  test("a non-admin cannot create a new local account", async ({ browser }) => {
    const mallory = `mallory_${Date.now()}`;
    const password = "mallory-pw";
    const victim = `created_by_mallory_${Date.now()}`;

    const admin = await newLoggedInContext(browser, "admin", "admin");
    await createUser(admin, mallory, password, { isSuperAdmin: false });

    const malloryPage = await newLoggedInContext(browser, mallory, password);
    await malloryPage.request.post(`${baseURL()}/permissions/saveUser`, {
      form: {
        username: victim,
        password: "victim-pw",
        label: victim,
        email: `${victim}@test.local`,
        isSuperAdmin: "On",
      },
    });

    expect(userExistsInDb(victim)).toBe(false);
  });
});

test.describe("Permissions::removeUser authorization", () => {
  test.beforeEach(() => {
    refreshDatabase();
  });

  test("a non-admin cannot delete another user", async ({ browser }) => {
    const mallory = `mallory_${Date.now()}`;
    const victim = `victim_${Date.now()}`;
    const password = "mallory-pw";

    const admin = await newLoggedInContext(browser, "admin", "admin");
    await createUser(admin, mallory, password, { isSuperAdmin: false });
    await createUser(admin, victim, "victim-pw", { isSuperAdmin: false });
    const victimId = userIdInDb(victim);

    const malloryPage = await newLoggedInContext(browser, mallory, password);
    await malloryPage.request.post(`${baseURL()}/permissions/removeUser`, {
      form: { userId: victimId },
    });

    expect(userExistsInDb(victim)).toBe(true);
  });

  test("an anonymous caller cannot delete a user", async ({ browser }) => {
    const victim = `victim_${Date.now()}`;

    const admin = await newLoggedInContext(browser, "admin", "admin");
    await createUser(admin, victim, "victim-pw", { isSuperAdmin: false });
    const victimId = userIdInDb(victim);

    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();
    await page.request.post(`${baseURL()}/permissions/removeUser`, {
      form: { userId: victimId },
    });

    expect(userExistsInDb(victim)).toBe(true);
  });

  test("an instance admin cannot delete a superadmin", async ({ browser }) => {
    const ian = `ian_${Date.now()}`;
    const target = `super_${Date.now()}`;

    const admin = await newLoggedInContext(browser, "admin", "admin");
    await createUser(admin, target, "super-pw", { isSuperAdmin: true });
    const targetId = userIdInDb(target);
    const ianPage = await newInstanceAdminContext(
      browser,
      admin,
      ian,
      "ian-pw",
    );

    await ianPage.request.post(`${baseURL()}/permissions/removeUser`, {
      form: { userId: targetId },
    });

    expect(userExistsInDb(target)).toBe(true);
  });

  test("an admin can still delete a user", async ({ browser }) => {
    const victim = `victim_${Date.now()}`;

    const admin = await newLoggedInContext(browser, "admin", "admin");
    await createUser(admin, victim, "victim-pw", { isSuperAdmin: false });
    const victimId = userIdInDb(victim);

    await admin.request.post(`${baseURL()}/permissions/removeUser`, {
      form: { userId: victimId },
    });

    expect(userExistsInDb(victim)).toBe(false);
  });
});

test.describe("Permissions::saveUser role-gated changes", () => {
  test.beforeEach(() => {
    refreshDatabase();
  });

  test("a non-admin cannot remove or extend their own expiry", async ({
    browser,
  }) => {
    const mallory = `mallory_${Date.now()}`;
    const password = "mallory-pw";

    const admin = await newLoggedInContext(browser, "admin", "admin");
    await createUser(admin, mallory, password, { isSuperAdmin: false });
    const malloryId = userIdInDb(mallory);
    await saveUser(admin, {
      userId: malloryId,
      username: mallory,
      password: "dontchangeme",
      label: mallory,
      email: `${mallory}@test.local`,
      hasExpiry: "On",
      expires: "12/31/2030",
    });
    expect(userColumnInDb(mallory, "hasexpiry")).toBe("t");

    const malloryPage = await newLoggedInContext(browser, mallory, password);
    await saveUser(malloryPage, {
      userId: malloryId,
      username: mallory,
      password: "dontchangeme",
      label: mallory,
      email: `${mallory}@test.local`,
      expires: "12/31/2099",
    });

    expect(userColumnInDb(mallory, "hasexpiry")).toBe("t");
    expect(userColumnInDb(mallory, "expires::date")).toBe("2030-12-31");
  });

  test("an instance admin can create a local user but cannot make them SuperAdmin", async ({
    browser,
  }) => {
    const ian = `ian_${Date.now()}`;
    const created = `created_by_ian_${Date.now()}`;

    const admin = await newLoggedInContext(browser, "admin", "admin");
    const ianPage = await newInstanceAdminContext(
      browser,
      admin,
      ian,
      "ian-pw",
    );

    await saveUser(ianPage, {
      username: created,
      password: "created-pw",
      label: created,
      email: `${created}@test.local`,
      isSuperAdmin: "On",
    });

    expect(userExistsInDb(created)).toBe(true);
    expect(isSuperAdminInDb(created)).toBe(false);
  });

  test("an instance admin saving a superadmin's profile leaves SuperAdmin set", async ({
    browser,
  }) => {
    const ian = `ian_${Date.now()}`;
    const target = `super_${Date.now()}`;

    const admin = await newLoggedInContext(browser, "admin", "admin");
    await createUser(admin, target, "super-pw", { isSuperAdmin: true });
    expect(isSuperAdminInDb(target)).toBe(true);
    const ianPage = await newInstanceAdminContext(
      browser,
      admin,
      ian,
      "ian-pw",
    );

    await saveUser(ianPage, {
      userId: userIdInDb(target),
      username: target,
      password: "dontchangeme",
      label: "Renamed by an instance admin",
      email: `${target}@test.local`,
    });

    expect(isSuperAdminInDb(target)).toBe(true);
  });

  test("an instance admin cannot change a superadmin's password", async ({
    browser,
  }) => {
    const ian = `ian_${Date.now()}`;
    const target = `super_${Date.now()}`;

    const admin = await newLoggedInContext(browser, "admin", "admin");
    await createUser(admin, target, "super-pw", { isSuperAdmin: true });
    const passwordHashBefore = userColumnInDb(target, "password");
    const ianPage = await newInstanceAdminContext(
      browser,
      admin,
      ian,
      "ian-pw",
    );

    await saveUser(ianPage, {
      userId: userIdInDb(target),
      username: target,
      password: "hijacked",
      label: target,
      email: `${target}@test.local`,
    });

    expect(userColumnInDb(target, "password")).toBe(passwordHashBefore);
  });
});

test.describe("Permissions::editUser authorization", () => {
  test("an instance admin cannot open a superadmin's edit form", async ({
    browser,
  }) => {
    const ian = `ian_${Date.now()}`;
    const target = `super_${Date.now()}`;

    const admin = await newLoggedInContext(browser, "admin", "admin");
    await createUser(admin, target, "super-pw", { isSuperAdmin: true });
    const ianPage = await newInstanceAdminContext(
      browser,
      admin,
      ian,
      "ian-pw",
    );

    const res = await ianPage.request.get(
      `${baseURL()}/permissions/editUser/${target}`,
    );

    expect(await res.text()).not.toContain('name="apisecret"');
  });

  test("an anonymous caller gets a 401 instead of the edit form", async ({
    browser,
  }) => {
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();

    const res = await page.request.get(
      `${baseURL()}/permissions/editUser/admin`,
    );

    expect(res.status()).toBe(401);
    expect(await res.text()).not.toContain('name="apisecret"');
  });
});
