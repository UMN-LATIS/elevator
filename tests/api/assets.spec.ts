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
  test.describe("deletedAssets", () => {
    test("returns 401 when not authenticated", async ({ browser }) => {
      const ctx = await browser.newContext();
      const req = ctx.request;
      const res = await req.get(`${baseURL()}/assetManager/deletedAssets`, {
        headers: { Accept: "application/json" },
      });
      expect(res.status()).toBe(401);
      const body = await res.json();
      expect(body).toHaveProperty("error");
      await ctx.close();
    });

    test("returns empty array when no assets are deleted", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/assetManager/deletedAssets`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(200);
      const body = await res.json();
      expect(Array.isArray(body)).toBe(true);
      expect(body).toHaveLength(0);
    });

    test("returns deleted assets as JSON", async ({ page }) => {
      const collectionId = await createCollection(
        page,
        "Deleted Assets Collection",
      );
      const template = await createTemplate(page, {
        name: "Deleted Assets Template",
      });
      const assetId = await createAsset(page, template.id, collectionId);

      // Soft-delete the asset.
      const deleteRes = await page.request.get(
        `${baseURL()}/assetManager/deleteAsset/${assetId}/true`,
      );
      expect(deleteRes.status()).toBe(204);

      const res = await page.request.get(
        `${baseURL()}/assetManager/deletedAssets`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(200);
      const body = await res.json();
      expect(Array.isArray(body)).toBe(true);
      expect(body.length).toBeGreaterThan(0);
      expect(body[0]).toHaveProperty("objectId");
      expect(body[0]).toHaveProperty("title");
      expect(body[0]).toHaveProperty("templateId");
      expect(body[0]).toHaveProperty("deletedAt");
      expect(body[0]).toHaveProperty("deletedBy");
      expect(body[0]).toHaveProperty("modifiedDate");
    });
  });
});
