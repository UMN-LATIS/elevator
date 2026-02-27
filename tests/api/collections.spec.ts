import { test, expect } from "@playwright/test";
import { loginUser, refreshDatabase, baseURL } from "../helpers";

test.describe("collections", () => {
  // Reset DB before suite starts so stale data from previous runs doesn't
  // interfere (bootstrap inserts base data without truncating first).
  test.beforeAll(async ({ request }) => {
    const response = await request.post(`${baseURL()}/testhelper/resetDb`);
    expect(response.ok()).toBeTruthy();
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
    // Ensure DB is clean even if the test fails before the in-test reset.
    await refreshDatabase(page);
  });

  test("db reset removes created collection", async ({ page }) => {
    // Create a collection via the admin form endpoint.
    // POST /{instance}/collectionmanager/save — redirects to collectionmanager/ on success.
    const createResponse = await page.request.post(
      `${baseURL()}/collectionmanager/save`,
      {
        form: {
          title: "Test Collection (should be reset)",
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
    expect(createResponse.status()).toBeLessThan(400);

    // Verify it exists via the admin collection manager page (session-auth HTML).
    const beforeReset = await page.request.get(
      `${baseURL()}/collectionmanager/`,
    );
    expect(await beforeReset.text()).toContain(
      "Test Collection (should be reset)",
    );

    // Reset DB.
    await refreshDatabase(page);

    // Verify it is gone.
    const afterReset = await page.request.get(
      `${baseURL()}/collectionmanager/`,
    );
    expect(await afterReset.text()).not.toContain(
      "Test Collection (should be reset)",
    );
  });
});
