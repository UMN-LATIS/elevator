import { test, expect } from "@playwright/test";
import { loginUser, refreshDatabase } from "../helpers";

const baseURL = process.env.BASE_URL ?? "http://localhost/defaultinstance";

test.describe("collections", () => {
  test.beforeEach(async ({ page }) => {
    const adminPassword = process.env.ADMIN_PASSWORD;
    if (!adminPassword) test.skip(true, "ADMIN_PASSWORD env var not set");
    await loginUser(
      page,
      process.env.ADMIN_USERNAME ?? "admin",
      adminPassword!,
    );
  });

  test("db reset removes created collection", async ({ page }) => {
    // Create a collection via the admin form endpoint.
    // POST /{instance}/collectionmanager/save — redirects to collectionmanager/ on success.
    const createResponse = await page.request.post(`${baseURL}/collectionmanager/save`, {
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
    });
    expect(createResponse.status()).toBeLessThan(400);

    // Verify it exists via the admin collection manager page (session-auth HTML).
    const beforeReset = await page.request.get(`${baseURL}/collectionmanager/`);
    expect(await beforeReset.text()).toContain(
      "Test Collection (should be reset)",
    );

    // Reset DB.
    await refreshDatabase(page);

    // Verify it is gone.
    const afterReset = await page.request.get(`${baseURL}/collectionmanager/`);
    expect(await afterReset.text()).not.toContain(
      "Test Collection (should be reset)",
    );
  });
});
