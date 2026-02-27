import { test, expect } from "@playwright/test";
import {
  loginUser,
  refreshDatabase,
  createTemplate,
  baseURL,
} from "../helpers";

// Templates API — baseline contract tests.
//
// Scope: auth, list, create, update, delete using the develop-era API surface
// (Templates::toTemplateSummary response shape). Tests for the richer
// getTemplate() endpoint and widgetArray shape live in feat-update-template-api-for-editor.

test.describe("templates", () => {
  test.beforeAll(async ({ request }) => {
    const response = await request.post(`${baseURL()}/testhelper/resetDb`);
    expect(response.ok()).toBe(true);
  });

  test.beforeEach(async ({ page }) => {
    const adminPassword = process.env.DEFAULT_ADMIN_PASSWORD;
    if (!adminPassword) {
      test.skip(true, "DEFAULT_ADMIN_PASSWORD env var not set");
      return;
    }
    await loginUser(page, process.env.ADMIN_USERNAME ?? "admin", adminPassword);
  });

  test.afterEach(async ({ page }) => {
    await refreshDatabase(page);
  });

  // --- auth ---

  test("unauthenticated JSON request returns 401", async ({ browser }) => {
    const ctx = await browser.newContext();
    const response = await ctx.request.get(`${baseURL()}/templates/`, {
      headers: { Accept: "application/json" },
    });
    expect(response.status()).toBe(401);
    await ctx.close();
  });

  // --- list ---

  test("returns array of templates", async ({ page }) => {
    const response = await page.request.get(`${baseURL()}/templates/`, {
      headers: { Accept: "application/json" },
    });
    expect(response.ok()).toBe(true);
    const body = (await response.json()) as unknown[];
    expect(Array.isArray(body)).toBe(true);
  });

  // --- create ---

  test("creates a template and returns summary", async ({ page }) => {
    const template = await createTemplate(page, { name: "My New Template" });
    expect(template.id).toBeGreaterThan(0);
    expect(template.name).toBe("My New Template");
    expect(template.createdAt).toBeTruthy();
    expect(template.modifiedAt).toBeTruthy();
  });

  test("created template appears in list", async ({ page }) => {
    const template = await createTemplate(page, { name: "Listed Template" });

    const listResponse = await page.request.get(`${baseURL()}/templates/`, {
      headers: { Accept: "application/json" },
    });
    const list = (await listResponse.json()) as Array<{
      id: number;
      name: string;
    }>;
    expect(list.some((t) => t.id === template.id)).toBe(true);
  });

  // --- update ---

  test("updates an existing template's name", async ({ page }) => {
    const created = await createTemplate(page, { name: "Original Name" });

    const updateResponse = await page.request.post(
      `${baseURL()}/templates/update`,
      {
        headers: { Accept: "application/json" },
        form: {
          templateId: String(created.id),
          name: "Updated Name",
          templateColor: "1",
          recursiveIndexDepth: "1",
          collectionPosition: "0",
          templatePosition: "0",
        },
      },
    );

    expect(updateResponse.ok()).toBe(true);
    const updated = (await updateResponse.json()) as {
      id: number;
      name: string;
    };
    expect(updated.id).toBe(created.id);
    expect(updated.name).toBe("Updated Name");
  });

  // --- delete ---

  test("deletes a template successfully", async ({ page }) => {
    const template = await createTemplate(page, { name: "To Be Deleted" });

    const deleteResponse = await page.request.get(
      `${baseURL()}/templates/delete/${template.id}`,
      { headers: { Accept: "application/json" } },
    );
    expect(deleteResponse.ok()).toBe(true);
    const body = (await deleteResponse.json()) as { success: boolean };
    expect(body.success).toBe(true);
  });

  test("deleted template no longer appears in list", async ({ page }) => {
    const template = await createTemplate(page, { name: "Gone Template" });

    await page.request.get(`${baseURL()}/templates/delete/${template.id}`, {
      headers: { Accept: "application/json" },
    });

    const listResponse = await page.request.get(`${baseURL()}/templates/`, {
      headers: { Accept: "application/json" },
    });
    const list = (await listResponse.json()) as Array<{ id: number }>;
    expect(list.some((t) => t.id === template.id)).toBe(false);
  });
});
