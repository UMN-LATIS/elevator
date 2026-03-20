import { execSync } from "child_process";
import path from "path";
import { type Page } from "@playwright/test";

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

// POST /{instance}/loginmanager/localLoginAsync
// Fields: username, password (application/x-www-form-urlencoded)
// Sets ci_session cookie on the page context on success.
export async function loginUser(
  page: Page,
  username: string,
  password: string,
): Promise<void> {
  const response = await page.request.post(
    `${baseURL()}/loginmanager/localLoginAsync`,
    {
      form: { username, password },
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
