import { test, expect } from "@playwright/test";
import {
  loginUser,
  refreshDatabase,
  baseURL,
  createTemplate,
  createCollection,
  createAsset,
  createUser,
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
      `${baseURL()}/assetManager/deleteAsset/${assetId}/true`,
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

  test.describe("restoreAsset (list revisions) JSON", () => {
    test("returns 401 when not authenticated", async ({ browser }) => {
      // Fresh context with no session cookie.
      const ctx = await browser.newContext();
      const req = ctx.request;
      const res = await req.get(
        `${baseURL()}/assetManager/restoreAsset/000000000000000000000000`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(401);
      const body = await res.json();
      expect(body).toHaveProperty("error");
      await ctx.close();
    });

    test("returns revision list as JSON", async ({ page }) => {
      const collectionId = await createCollection(page, "Restore Test Collection");
      const template = await createTemplate(page, {
        name: "Restore Test Template",
      });
      const assetId = await createAsset(page, template.id, collectionId);

      // Re-submit the asset to create a revision.
      await page.request.post(`${baseURL()}/assetManager/submission/true`, {
        form: {
          formData: JSON.stringify({
            objectId: assetId,
            templateId: template.id,
            collectionId,
          }),
        },
      });

      const res = await page.request.get(
        `${baseURL()}/assetManager/restoreAsset/${assetId}`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(200);
      const body = await res.json();
      expect(Array.isArray(body)).toBe(true);
      expect(body.length).toBeGreaterThan(0);
      expect(body[0]).toHaveProperty("indexId");
      expect(body[0]).toHaveProperty("modifiedDate");
    });
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

    test("only returns assets deleted by the current user", async ({
      page,
      browser,
    }) => {
      const collectionId = await createCollection(
        page,
        "Scoping Test Collection",
      );
      const template = await createTemplate(page, {
        name: "Scoping Test Template",
      });
      const assetId = await createAsset(page, template.id, collectionId);

      // Admin deletes the asset.
      const deleteRes = await page.request.get(
        `${baseURL()}/assetManager/deleteAsset/${assetId}/true`,
      );
      expect(deleteRes.status()).toBe(204);

      // Sanity: admin sees the deleted asset.
      const adminRes = await page.request.get(
        `${baseURL()}/assetManager/deletedAssets`,
        { headers: { Accept: "application/json" } },
      );
      expect(adminRes.status()).toBe(200);
      const adminBody = await adminRes.json();
      expect(adminBody.length).toBeGreaterThan(0);

      // Create a second superadmin user.
      await createUser(page, "testuser2", "testpass2", {
        isSuperAdmin: true,
      });

      // Log in as the second user in a fresh context.
      const ctx = await browser.newContext();
      const user2Page = await ctx.newPage();
      await loginUser(user2Page, "testuser2", "testpass2");

      // Second user should see no deleted assets (they didn't delete any).
      const user2Res = await user2Page.request.get(
        `${baseURL()}/assetManager/deletedAssets`,
        { headers: { Accept: "application/json" } },
      );
      expect(user2Res.status()).toBe(200);
      const user2Body = await user2Res.json();
      expect(user2Body).toHaveLength(0);

      await ctx.close();
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
      expect(body[0]).toHaveProperty("readyForDisplay");
      expect(body[0]).toHaveProperty("templateId");
      expect(body[0]).toHaveProperty("modifiedDate");
      expect(body[0]).toHaveProperty("deletedAt");
      expect(body[0]).toHaveProperty("deletedBy");
    });
  });

  test.describe("undeleteAsset", () => {
    test("returns 405 for GET requests", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/assetManager/undeleteAsset/000000000000000000000000`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(405);
    });

    test("returns 401 when not authenticated", async ({ browser }) => {
      const ctx = await browser.newContext();
      const req = ctx.request;
      const res = await req.post(
        `${baseURL()}/assetManager/undeleteAsset/000000000000000000000000`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(401);
      const body = await res.json();
      expect(body).toHaveProperty("error");
      await ctx.close();
    });

    test("returns 404 for non-existent asset", async ({ page }) => {
      const res = await page.request.post(
        `${baseURL()}/assetManager/undeleteAsset/000000000000000000000000`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(404);
      const body = await res.json();
      expect(body).toHaveProperty("error");
    });

    test("undeletes a soft-deleted asset and returns JSON", async ({
      page,
    }) => {
      const collectionId = await createCollection(
        page,
        "Undelete Test Collection",
      );
      const template = await createTemplate(page, {
        name: "Undelete Test Template",
      });
      const assetId = await createAsset(page, template.id, collectionId);

      // Soft-delete the asset.
      const deleteRes = await page.request.get(
        `${baseURL()}/assetManager/deleteAsset/${assetId}/true`,
      );
      expect(deleteRes.status()).toBe(204);

      // Confirm it's gone.
      const gone = await page.request.get(
        `${baseURL()}/asset/viewAsset/${assetId}/true`,
      );
      expect(gone.status()).toBe(404);

      // Undelete the asset.
      const undeleteRes = await page.request.post(
        `${baseURL()}/assetManager/undeleteAsset/${assetId}`,
        { headers: { Accept: "application/json" } },
      );
      expect(undeleteRes.status()).toBe(200);
      const body = await undeleteRes.json();
      expect(body).toHaveProperty("objectId", assetId);

      // Verify the asset is accessible again.
      const afterUndelete = await page.request.get(
        `${baseURL()}/asset/viewAsset/${assetId}/true`,
      );
      expect(afterUndelete.status()).toBe(200);
    });
  });

  test.describe("restore (perform restore) JSON", () => {
    test("returns 401 when not authenticated", async ({ browser }) => {
      const ctx = await browser.newContext();
      const req = ctx.request;
      const res = await req.get(
        `${baseURL()}/assetManager/restore/999999`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(401);
      const body = await res.json();
      expect(body).toHaveProperty("error");
      await ctx.close();
    });

    test("returns 404 when no revision source exists", async ({ page }) => {
      // Use a non-existent DB primary key — find() returns null.
      const res = await page.request.get(
        `${baseURL()}/assetManager/restore/999999`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(404);
      const body = await res.json();
      expect(body).toHaveProperty("error");
    });

    test("restores a soft-deleted asset and returns JSON", async ({ page }) => {
      const collectionId = await createCollection(
        page,
        "Restore Success Collection",
      );
      const template = await createTemplate(page, {
        name: "Restore Success Template",
      });
      const assetId = await createAsset(page, template.id, collectionId);

      // Re-submit to create a revision.
      await page.request.post(`${baseURL()}/assetManager/submission/true`, {
        form: {
          formData: JSON.stringify({
            objectId: assetId,
            templateId: template.id,
            collectionId,
          }),
        },
      });

      // Get the revision list to find a revision indexId.
      const listRes = await page.request.get(
        `${baseURL()}/assetManager/restoreAsset/${assetId}`,
        { headers: { Accept: "application/json" } },
      );
      const revisions = await listRes.json();
      const revisionIndexId = revisions[0].indexId;

      // Soft-delete the asset.
      const deleteRes = await page.request.get(
        `${baseURL()}/assetManager/deleteAsset/${assetId}/true`,
      );
      expect(deleteRes.status()).toBe(204);

      // Restore via the revision.
      const restoreRes = await page.request.get(
        `${baseURL()}/assetManager/restore/${revisionIndexId}`,
        { headers: { Accept: "application/json" } },
      );
      expect(restoreRes.status()).toBe(200);
      const body = await restoreRes.json();
      expect(body).toHaveProperty("objectId");

      // Verify the asset is accessible again.
      const afterRestore = await page.request.get(
        `${baseURL()}/asset/viewAsset/${body.objectId}/true`,
      );
      expect(afterRestore.status()).toBe(200);
    });
  });
});
