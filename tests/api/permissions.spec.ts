import { test, expect } from "@playwright/test";
import { loginUser, baseURL } from "../helpers";

test.describe("permissions", () => {
  test.describe("GET /permissions/permissionLevels", () => {
    test("returns 401 when not authenticated", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/permissions/permissionLevels`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(401);
    });

    test.describe("when authenticated", () => {
      test.beforeEach(async ({ page }) => {
        await loginUser(page, "admin");
      });

      test("returns 200 with permissionLevels array", async ({ page }) => {
        const res = await page.request.get(
          `${baseURL()}/permissions/permissionLevels`,
          { headers: { Accept: "application/json" } },
        );
        expect(res.status()).toBe(200);

        const body = await res.json();
        expect(body).toHaveProperty("permissionLevels");
        expect(Array.isArray(body.permissionLevels)).toBe(true);
        expect(body.permissionLevels.length).toBeGreaterThan(0);
      });

      test("each level has id, level, name, label fields", async ({ page }) => {
        const res = await page.request.get(
          `${baseURL()}/permissions/permissionLevels`,
          { headers: { Accept: "application/json" } },
        );
        const { permissionLevels } = await res.json();

        for (const item of permissionLevels) {
          expect(typeof item.id).toBe("number");
          expect(typeof item.level).toBe("number");
          expect(typeof item.name).toBe("string");
          expect(typeof item.label).toBe("string");
        }
      });

      test("levels are sorted ascending by level", async ({ page }) => {
        const res = await page.request.get(
          `${baseURL()}/permissions/permissionLevels`,
          { headers: { Accept: "application/json" } },
        );
        const { permissionLevels } = await res.json();

        const levels = permissionLevels.map(
          (p: { level: number }) => p.level,
        );
        expect(levels).toEqual([...levels].sort((a, b) => a - b));
      });
    });
  });
});
