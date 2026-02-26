import { test, expect } from "@playwright/test";
import { loginUser, refreshDatabase } from "../helpers";

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
    const createResponse = await page.request.post("/collectionmanager/save", {
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

    // Verify it exists in the API listing.
    const beforeReset = await page.request.get(
      "/api/v1/collections/listCollections",
    );
    const beforeJson = (await beforeReset.json()) as Record<string, string>;
    expect(Object.values(beforeJson)).toContain(
      "Test Collection (should be reset)",
    );

    // Reset DB.
    await refreshDatabase(page);

    // Verify it is gone.
    const afterReset = await page.request.get(
      "/api/v1/collections/listCollections",
    );
    const afterJson = (await afterReset.json()) as Record<string, string>;
    expect(Object.values(afterJson)).not.toContain(
      "Test Collection (should be reset)",
    );
  });
});
