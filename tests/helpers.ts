import { type Page } from "@playwright/test";

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
// Requires CI_ENV=local or CI_ENV=testing on the server (returns 403 in production).
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
