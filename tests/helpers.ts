import { execSync } from "child_process";
import path from "path";
import { expect, type Browser, type Page } from "@playwright/test";

export interface CreateTemplateOptions {
  name?: string;
}

// Matches Templates::toTemplateSummary() — the shape returned by update() in the
// current develop branch. A richer shape (widgetArray, display flags, etc.) will
// be added when the template-editor feature lands.
export interface TemplateResponse {
  id: number;
  name: string;
  createdAt: string;
  modifiedAt: string;
}

export const baseURL = (): string =>
  process.env.BASE_URL ?? "http://localhost/defaultinstance";

// POST /{instance}/loginManager/localLoginAsync
// Fields: username, password (application/x-www-form-urlencoded)
// Sets ci_session cookie on the page context on success.
export async function loginUser(
  page: Page,
  username: string,
  password?: string,
): Promise<void> {
  const response = await page.request.post(
    `${baseURL()}/loginManager/localLoginAsync`,
    {
      form: { username, password: password || username },
    },
  );

  if (!response.ok()) {
    const body = (await response.json().catch(() => ({}))) as {
      message?: string;
    };
    throw new Error(
      `Login failed (${response.status()}): ${body.message ?? "unknown error"}`,
    );
  }

  // ci_session cookie is set automatically on the page.request context.
  // Subsequent page.goto() calls in the same context will include it.
}

// Truncates user-writable tables and resets sequences to seed state by running
// scripts/reset-test-db.sh, which talks to postgres directly via psql inside
// the running docker compose postgres container.
export function refreshDatabase(): void {
  const projectRoot = path.resolve(__dirname, "..");
  execSync("./scripts/reset-test-db.sh", { cwd: projectRoot, stdio: "pipe" });
}

// POST /{instance}/templates/update (no templateId = create)
// Sends form-encoded data matching the legacy template form.
// Returns the Templates::toTemplateSummary() shape (id, name, createdAt, modifiedAt).
// Widget support will be added when the template-editor feature lands.
export async function createTemplate(
  page: Page,
  options: CreateTemplateOptions = {},
): Promise<TemplateResponse> {
  const name = options.name ?? "Test Template";

  const response = await page.request.post(`${baseURL()}/templates/update`, {
    headers: { Accept: "application/json" },
    form: {
      name,
      templateColor: "1",
      recursiveIndexDepth: "1",
      collectionPosition: "0",
      templatePosition: "0",
    },
  });

  if (!response.ok()) {
    const body = (await response.json().catch(() => ({}))) as {
      error?: string;
    };
    throw new Error(
      `createTemplate failed (${response.status()}): ${
        body.error ?? "unknown"
      }`,
    );
  }

  return response.json() as Promise<TemplateResponse>;
}

// POST /{instance}/collectionManager/save
// Returns the new collection's id and title.
// Sends Accept: application/json so the endpoint returns JSON instead of redirecting.
export async function createCollection(
  page: Page,
  title = "Test Collection",
): Promise<number> {
  const response = await page.request.post(
    `${baseURL()}/collectionManager/save`,
    {
      headers: { Accept: "application/json" },
      form: {
        title,
        bucket: "",
        bucketRegion: "",
        S3Key: "",
        S3Secret: "",
        showInBrowse: "on",
        collectionDescription: "",
        previewImage: "",
        parent: "0",
      },
    },
  );

  if (!response.ok()) {
    const body = (await response.json().catch(() => ({}))) as {
      error?: string;
    };
    throw new Error(
      `createCollection failed (${response.status()}): ${body.error ?? "unknown"}`,
    );
  }

  const body = (await response.json()) as { id: number; title: string };
  return body.id;
}

// POST /{instance}/assetManager/submission/true
// formData is a JSON string containing at minimum templateId and collectionId.
// Returns the new asset's objectId (a 24-char hex MongoDB-style ID).
// POST /{instance}/permissions/saveUser
// Creates a local user via the admin permissions controller.
// The caller must be logged in as an admin.
// An already-taken username is a no-op, leaving the existing user untouched.
export async function createUser(
  page: Page,
  username: string,
  password: string,
  options: { isSuperAdmin?: boolean; displayName?: string; email?: string } = {},
): Promise<void> {
  const response = await page.request.post(
    `${baseURL()}/permissions/saveUser`,
    {
      form: {
        username,
        password,
        label: options.displayName ?? username,
        email: options.email ?? `${username}@test.local`,
        isSuperAdmin: options.isSuperAdmin ? "1" : "0",
      },
    },
  );

  if (!response.ok()) {
    const text = await response.text().catch(() => "unknown");
    throw new Error(`createUser failed (${response.status()}): ${text}`);
  }
}

export async function createAsset(
  page: Page,
  templateId: number,
  collectionId: number,
): Promise<string> {
  const formData = JSON.stringify({ templateId, collectionId });

  const response = await page.request.post(
    `${baseURL()}/assetManager/submission/true`,
    { form: { formData } },
  );

  if (!response.ok()) {
    const body = (await response.json().catch(() => ({}))) as {
      error?: string;
    };
    throw new Error(
      `createAsset failed (${response.status()}): ${body.error ?? "unknown"}`,
    );
  }

  const body = (await response.json()) as { objectId: string; success: boolean };
  return body.objectId;
}

export const PERM_ADMIN = 60;

export function queryDb(sql: string): string {
  return execSync(
    `docker compose exec -T postgres psql -U elevator -d elevator -tA -c "${sql}"`,
    { cwd: path.resolve(__dirname, ".."), encoding: "utf8" },
  ).trim();
}

export function userColumnInDb(username: string, column: string): string {
  return queryDb(`SELECT ${column} FROM users WHERE username = '${username}'`);
}

export function isSuperAdminInDb(username: string): boolean {
  return userColumnInDb(username, "issuperadmin") === "t";
}

export function userIdInDb(username: string): string {
  const id = userColumnInDb(username, "id");
  expect(id, `user ${username} should exist`).not.toBe("");
  return id;
}

export async function newLoggedInContext(
  browser: Browser,
  username: string,
  password: string,
): Promise<Page> {
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();
  await loginUser(page, username, password);
  return page;
}

export async function newInstanceAdminContext(
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
