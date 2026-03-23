import { test, expect } from "@playwright/test";
import {
  loginUser,
  refreshDatabase,
  baseURL,
  createTemplate,
  createCollection,
  createAsset,
} from "../helpers";

test.describe("assets", () => {
  test.beforeAll(() => {
    refreshDatabase();
  });

  test.beforeEach(async ({ page }) => {
    const adminPassword = process.env.DEFAULT_ADMIN_PASSWORD;
    if (!adminPassword) {
      test.skip(true, "DEFAULT_ADMIN_PASSWORD env var not set");
      return;
    }
    await loginUser(page, process.env.ADMIN_USERNAME ?? "admin", adminPassword);
  });

  test.afterEach(() => {
    refreshDatabase();
  });

  // Regression test for issue #467: deleting an asset and then navigating to
  // its URL caused a 500 (for assets with uploads) or returned stale data
  // (bare assets). The fix adds a deleted check in Asset::getAsset() and
  // Asset::viewAsset() after loadAssetById() succeeds.
  test("deleted asset returns 404, not 200", async ({ page }) => {
    const collectionId = await createCollection(page, "Test Collection #467");
    const template = await createTemplate(page, { name: "Test Template #467" });
    const assetId = await createAsset(page, template.id, collectionId);

    // Sanity check: asset is accessible before deletion.
    const beforeDelete = await page.request.get(
      `${baseURL()}/asset/viewAsset/${assetId}/true`,
    );
    expect(beforeDelete.status()).toBe(200);

    // Delete the asset (soft-delete).
    const deleteRes = await page.request.get(
      `${baseURL()}/assetmanager/deleteAsset/${assetId}/true`,
    );
    expect(deleteRes.status()).toBe(204);

    // getAsset must return 404 for a soft-deleted asset.
    const getRes = await page.request.get(
      `${baseURL()}/asset/getAsset/${assetId}`,
    );
    expect(getRes.status()).toBe(404);

    // viewAsset must also return 404.
    const viewRes = await page.request.get(
      `${baseURL()}/asset/viewAsset/${assetId}/true`,
    );
    expect(viewRes.status()).toBe(404);
  });
});
