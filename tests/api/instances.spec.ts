import { test, expect } from "@playwright/test";
import { loginUser, baseURL } from "../helpers";

test.describe("instances/edit in a Vue UI session", () => {
  test.beforeEach(async ({ page }) => {
    await loginUser(page, "admin");
    await page.request.get(`${baseURL()}/instances/previewNewInterface`);
  });

  test("without an id serves the legacy create form", async ({ page }) => {
    const response = await page.request.get(`${baseURL()}/instances/edit`);
    expect(await response.text()).toContain("instances/save");
  });

  test("with an id serves the Vue app", async ({ page }) => {
    const response = await page.request.get(`${baseURL()}/instances/edit/1`);
    expect(await response.text()).toContain('<div id="app">');
  });
});
