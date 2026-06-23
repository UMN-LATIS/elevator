import { test, expect } from "@playwright/test";
import { loginUser, refreshDatabase, baseURL } from "../helpers";

// Endpoints under AdminPermissions.php, routed at /{instance}/adminPermissions.
// All require an admin session (abortUnlessAdmin -> 401 when not).

async function loginAdmin(page: import("@playwright/test").Page) {
  await loginUser(page, process.env.ADMIN_USERNAME ?? "admin", process.env.DEFAULT_ADMIN_PASSWORD ?? 'admin');
}

test.describe("adminPermissions", () => {
  test.describe("unauthenticated", () => {
    // Each protected endpoint should reject an anonymous request with 401.
    for (const path of [
      "/adminPermissions/groupTypes",
      "/adminPermissions/permissionLevels",
      "/adminPermissions/groups",
    ]) {
      test(`GET ${path} returns 401`, async ({ page }) => {
        const res = await page.request.get(`${baseURL()}${path}`, {
          headers: { Accept: "application/json" },
        });
        expect(res.status()).toBe(401);
      });
    }
  });

  test.describe("authenticated reads", () => {
    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test("GET /groupTypes lists the known group types", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/adminPermissions/groupTypes`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(200);

      const { groupTypes } = await res.json();
      expect(Array.isArray(groupTypes)).toBe(true);
      // The global types are always present regardless of auth helper.
      expect(groupTypes).toEqual(
        expect.arrayContaining(["All", "Authed", "Authed_remote", "User"]),
      );
    });

    test("GET /permissionLevels returns levels sorted ascending", async ({
      page,
    }) => {
      const res = await page.request.get(
        `${baseURL()}/adminPermissions/permissionLevels`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(200);

      const { permissionLevels } = await res.json();
      expect(Array.isArray(permissionLevels)).toBe(true);
      expect(permissionLevels.length).toBeGreaterThan(0);

      // level is a string column the controller sorts numerically.
      const levels = permissionLevels.map((p: { level: unknown }) =>
        Number(p.level),
      );
      expect(levels).toEqual([...levels].sort((a, b) => a - b));
    });

    test("GET /groups returns a groups array", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/adminPermissions/groups`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(200);

      const { groups } = await res.json();
      expect(Array.isArray(groups)).toBe(true);
    });

    test("unsupported verb on /groups returns 405", async ({ page }) => {
      // The controller dispatches on REQUEST_METHOD; DELETE is not handled.
      const res = await page.request.delete(
        `${baseURL()}/adminPermissions/groups`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(405);
    });
  });

  test.describe("POST /groups (create)", () => {
    // These mutate, so reset around the whole suite (mirrors collections.spec).
    test.beforeAll(() => {
      refreshDatabase();
    });

    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test.afterEach(() => {
      refreshDatabase();
    });

    test("creates a whole-population group (All) with the scalar value", async ({
      page,
    }) => {
      const res = await page.request.post(
        `${baseURL()}/adminPermissions/groups`,
        { form: { type: "All", label: "Everyone" } },
      );
      expect(res.status()).toBe(201);

      const { group } = await res.json();
      expect(group.type).toBe("All");
      expect(group.label).toBe("Everyone");
      // Vestigial scalar must be 1 so Authed/Remote matching keeps working.
      expect(group.value).toBe(1);
      expect(group.values).toEqual([]);
    });

    test("creates a value-based group (User) with entries", async ({ page }) => {
      const res = await page.request.post(
        `${baseURL()}/adminPermissions/groups`,
        {
          // Numeric user values are stored as-is (no remote-id resolution).
          form: { type: "User", label: "Specific people", "values[0]": "1" },
        },
      );
      expect(res.status()).toBe(201);

      const { group } = await res.json();
      expect(group.type).toBe("User");
      expect(group.value).toBeNull();
      expect(group.values).toHaveLength(1);
      expect(group.values[0]).toHaveProperty("value");
    });

    test("a created group appears in the list", async ({ page }) => {
      await page.request.post(`${baseURL()}/adminPermissions/groups`, {
        form: { type: "All", label: "Findable group" },
      });

      const res = await page.request.get(
        `${baseURL()}/adminPermissions/groups`,
        { headers: { Accept: "application/json" } },
      );
      const { groups } = await res.json();
      expect(
        groups.some((g: { label: string }) => g.label === "Findable group"),
      ).toBe(true);
    });

    test("rejects a missing type", async ({ page }) => {
      const res = await page.request.post(
        `${baseURL()}/adminPermissions/groups`,
        { form: { label: "No type" } },
      );
      expect(res.status()).toBe(422);
      expect((await res.json()).errors).toHaveProperty("type");
    });

    test("rejects an unknown type", async ({ page }) => {
      const res = await page.request.post(
        `${baseURL()}/adminPermissions/groups`,
        { form: { type: "Nonsense", label: "Bad type" } },
      );
      expect(res.status()).toBe(422);
      expect((await res.json()).errors).toHaveProperty("type");
    });

    test("rejects a missing label", async ({ page }) => {
      const res = await page.request.post(
        `${baseURL()}/adminPermissions/groups`,
        { form: { type: "All" } },
      );
      expect(res.status()).toBe(422);
      expect((await res.json()).errors).toHaveProperty("label");
    });

    test("rejects values on a whole-population type", async ({ page }) => {
      // An "All" group must not pose as a "specific people" one.
      const res = await page.request.post(
        `${baseURL()}/adminPermissions/groups`,
        { form: { type: "All", label: "All but sneaky", "values[0]": "1" } },
      );
      expect(res.status()).toBe(422);
      expect((await res.json()).errors).toHaveProperty("values");
    });

    test("rejects a value-based type with no values", async ({ page }) => {
      const res = await page.request.post(
        `${baseURL()}/adminPermissions/groups`,
        { form: { type: "User", label: "Empty people" } },
      );
      expect(res.status()).toBe(422);
      expect((await res.json()).errors).toHaveProperty("values");
    });
  });
});
