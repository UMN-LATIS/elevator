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

// POST /{instance}/testhelper/resetDb
// Truncates user-writable tables back to seed state.
// Requires CI_ENV=local or CI_ENV=testing on the server (sets CodeIgniter's ENVIRONMENT constant); returns 403 in production.
export async function refreshDatabase(page: Page): Promise<void> {
  const response = await page.request.post(`${baseURL()}/testhelper/resetDb`);

  if (!response.ok()) {
    const body = (await response.json().catch(() => ({}))) as {
      message?: string;
    };
    throw new Error(
      `DB reset failed (${response.status()}): ${
        body.message ?? "unknown error"
      }`,
    );
  }
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
