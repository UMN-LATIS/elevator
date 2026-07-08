import { test, expect, type Page } from "@playwright/test";
import {
  loginUser,
  refreshDatabase,
  baseURL,
  createCollection,
} from "../helpers";

// Grant endpoints under AdminPermissions.php. A grant attaches a permission
// level to a group, instance-wide (instanceGrants) or per collection
// (collectionGrants). All require an admin session.

async function loginAdmin(page: Page) {
  await loginUser(page, "admin");
}

type Group = {
  id: number;
  type: string;
  label: string;
};

// Grants reference a group by id, so give each test a real one to point at.
async function createGroup(page: Page, label: string): Promise<Group> {
  const res = await page.request.post(`${baseURL()}/adminPermissions/groups`, {
    form: { type: "All", label },
  });
  expect(res.status()).toBe(201);
  return (await res.json()).group as Group;
}

type PermissionLevel = {
  id: number;
  level: number;
};

// The seed DB ships the standard permission levels. Read their real ids
// instead of hard-coding them.
async function getPermissionLevels(page: Page): Promise<PermissionLevel[]> {
  const res = await page.request.get(
    `${baseURL()}/adminPermissions/permissionLevels`,
    { headers: { Accept: "application/json" } },
  );
  expect(res.status()).toBe(200);
  const { permissionLevels } = (await res.json()) as {
    permissionLevels: PermissionLevel[];
  };
  expect(permissionLevels.length).toBeGreaterThan(0);
  return permissionLevels;
}

type InstanceGrant = {
  id: number;
  groupId: number;
  permissionLevelId: number;
};

async function createInstanceGrant(
  page: Page,
  groupId: number,
  permissionLevelId: number,
): Promise<InstanceGrant> {
  const res = await page.request.post(
    `${baseURL()}/adminPermissions/instanceGrants`,
    {
      form: {
        groupId: String(groupId),
        permissionLevelId: String(permissionLevelId),
      },
    },
  );
  expect(res.status()).toBe(201);
  return (await res.json()).instanceGrant as InstanceGrant;
}

type CollectionGrant = InstanceGrant & {
  collectionId: number;
};

async function createCollectionGrant(
  page: Page,
  collectionId: number,
  groupId: number,
  permissionLevelId: number,
): Promise<CollectionGrant> {
  const res = await page.request.post(
    `${baseURL()}/adminPermissions/collectionGrants`,
    {
      form: {
        collectionId: String(collectionId),
        groupId: String(groupId),
        permissionLevelId: String(permissionLevelId),
      },
    },
  );
  expect(res.status()).toBe(201);
  return (await res.json()).collectionGrant as CollectionGrant;
}

test.describe("adminPermissions grants", () => {
  test.describe("unauthenticated", () => {
    for (const path of [
      "/adminPermissions/instanceGrants",
      "/adminPermissions/instanceGrants/1",
      "/adminPermissions/collectionGrants",
      "/adminPermissions/collectionGrants/1",
    ]) {
      test(`GET ${path} returns 401`, async ({ page }) => {
        const res = await page.request.get(`${baseURL()}${path}`, {
          headers: { Accept: "application/json" },
        });
        expect(res.status()).toBe(401);
      });
    }
  });

  test.describe("instanceGrants", () => {
    test.beforeAll(() => {
      refreshDatabase();
    });

    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test.afterEach(() => {
      refreshDatabase();
    });

    test("creates, lists, updates, and deletes a grant", async ({ page }) => {
      const group = await createGroup(page, "Instance Grantees");
      const levels = await getPermissionLevels(page);

      const grant = await createInstanceGrant(page, group.id, levels[0].id);
      expect(grant.groupId).toBe(group.id);
      expect(grant.permissionLevelId).toBe(levels[0].id);

      const list = await page.request.get(
        `${baseURL()}/adminPermissions/instanceGrants`,
        { headers: { Accept: "application/json" } },
      );
      expect(list.status()).toBe(200);
      const { instanceGrants } = (await list.json()) as {
        instanceGrants: InstanceGrant[];
      };
      expect(instanceGrants.some((g) => g.id === grant.id)).toBe(true);

      const newLevel = levels[levels.length - 1];
      const update = await page.request.put(
        `${baseURL()}/adminPermissions/instanceGrants/${grant.id}`,
        {
          form: {
            groupId: String(group.id),
            permissionLevelId: String(newLevel.id),
          },
        },
      );
      expect(update.status()).toBe(200);
      const updated = (await update.json()) as { instanceGrant: InstanceGrant };
      expect(updated.instanceGrant.permissionLevelId).toBe(newLevel.id);

      const remove = await page.request.delete(
        `${baseURL()}/adminPermissions/instanceGrants/${grant.id}`,
        { headers: { Accept: "application/json" } },
      );
      expect(remove.status()).toBe(200);
      expect((await remove.json()).deleted).toBe(grant.id);

      const afterDelete = await page.request.get(
        `${baseURL()}/adminPermissions/instanceGrants`,
        { headers: { Accept: "application/json" } },
      );
      const remaining = (await afterDelete.json()) as {
        instanceGrants: InstanceGrant[];
      };
      expect(remaining.instanceGrants.some((g) => g.id === grant.id)).toBe(
        false,
      );
    });

    test("rejects a second grant for the same group with 409", async ({
      page,
    }) => {
      const group = await createGroup(page, "Already Granted");
      const levels = await getPermissionLevels(page);
      const existing = await createInstanceGrant(page, group.id, levels[0].id);

      const res = await page.request.post(
        `${baseURL()}/adminPermissions/instanceGrants`,
        {
          form: {
            groupId: String(group.id),
            permissionLevelId: String(levels[0].id),
          },
        },
      );
      expect(res.status()).toBe(409);
      expect((await res.json()).existingGrantId).toBe(existing.id);
    });

    test("updating a grant onto an already-granted group returns 409", async ({
      page,
    }) => {
      const groupA = await createGroup(page, "Group A");
      const groupB = await createGroup(page, "Group B");
      const levels = await getPermissionLevels(page);
      await createInstanceGrant(page, groupA.id, levels[0].id);
      const grantB = await createInstanceGrant(page, groupB.id, levels[0].id);

      const res = await page.request.put(
        `${baseURL()}/adminPermissions/instanceGrants/${grantB.id}`,
        {
          form: {
            groupId: String(groupA.id),
            permissionLevelId: String(levels[0].id),
          },
        },
      );
      expect(res.status()).toBe(409);
    });

    test("rejects an unknown group or permission level with 422", async ({
      page,
    }) => {
      const group = await createGroup(page, "Validation Target");
      const levels = await getPermissionLevels(page);

      const badGroup = await page.request.post(
        `${baseURL()}/adminPermissions/instanceGrants`,
        {
          form: {
            groupId: "99999999",
            permissionLevelId: String(levels[0].id),
          },
        },
      );
      expect(badGroup.status()).toBe(422);

      const badLevel = await page.request.post(
        `${baseURL()}/adminPermissions/instanceGrants`,
        { form: { groupId: String(group.id), permissionLevelId: "99999999" } },
      );
      expect(badLevel.status()).toBe(422);
    });

    test("rejects a missing groupId with 422", async ({ page }) => {
      const levels = await getPermissionLevels(page);

      const res = await page.request.post(
        `${baseURL()}/adminPermissions/instanceGrants`,
        { form: { permissionLevelId: String(levels[0].id) } },
      );
      expect(res.status()).toBe(422);
      expect((await res.json()).errors).toHaveProperty("groupId");
    });

    test("updating or deleting a missing grant returns 404", async ({
      page,
    }) => {
      const group = await createGroup(page, "No Grant");
      const levels = await getPermissionLevels(page);

      const update = await page.request.put(
        `${baseURL()}/adminPermissions/instanceGrants/99999999`,
        {
          form: {
            groupId: String(group.id),
            permissionLevelId: String(levels[0].id),
          },
        },
      );
      expect(update.status()).toBe(404);

      const remove = await page.request.delete(
        `${baseURL()}/adminPermissions/instanceGrants/99999999`,
        { headers: { Accept: "application/json" } },
      );
      expect(remove.status()).toBe(404);
    });

    test("GET by id is unsupported and returns 405", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/adminPermissions/instanceGrants/1`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(405);
    });

    test("returns 400 for a non-numeric id", async ({ page }) => {
      const res = await page.request.delete(
        `${baseURL()}/adminPermissions/instanceGrants/not-a-number`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(400);
    });
  });

  test.describe("collectionGrants", () => {
    test.beforeAll(() => {
      refreshDatabase();
    });

    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test.afterEach(() => {
      refreshDatabase();
    });

    test("creates, lists, updates, and deletes a grant", async ({ page }) => {
      const collectionId = await createCollection(page, "Granted Collection");
      const group = await createGroup(page, "Collection Grantees");
      const levels = await getPermissionLevels(page);

      const grant = await createCollectionGrant(
        page,
        collectionId,
        group.id,
        levels[0].id,
      );
      expect(grant.collectionId).toBe(collectionId);
      expect(grant.groupId).toBe(group.id);
      expect(grant.permissionLevelId).toBe(levels[0].id);

      const list = await page.request.get(
        `${baseURL()}/adminPermissions/collectionGrants`,
        { headers: { Accept: "application/json" } },
      );
      expect(list.status()).toBe(200);
      const { collectionGrants } = (await list.json()) as {
        collectionGrants: CollectionGrant[];
      };
      expect(collectionGrants.some((g) => g.id === grant.id)).toBe(true);

      const newLevel = levels[levels.length - 1];
      const update = await page.request.put(
        `${baseURL()}/adminPermissions/collectionGrants/${grant.id}`,
        {
          form: {
            collectionId: String(collectionId),
            groupId: String(group.id),
            permissionLevelId: String(newLevel.id),
          },
        },
      );
      expect(update.status()).toBe(200);
      const updated = (await update.json()) as {
        collectionGrant: CollectionGrant;
      };
      expect(updated.collectionGrant.permissionLevelId).toBe(newLevel.id);

      const remove = await page.request.delete(
        `${baseURL()}/adminPermissions/collectionGrants/${grant.id}`,
        { headers: { Accept: "application/json" } },
      );
      expect(remove.status()).toBe(200);
      expect((await remove.json()).deleted).toBe(grant.id);
    });

    test("rejects a second grant for the same group and collection with 409", async ({
      page,
    }) => {
      const collectionId = await createCollection(page, "Dupe Collection");
      const group = await createGroup(page, "Dupe Grantees");
      const levels = await getPermissionLevels(page);
      const existing = await createCollectionGrant(
        page,
        collectionId,
        group.id,
        levels[0].id,
      );

      const res = await page.request.post(
        `${baseURL()}/adminPermissions/collectionGrants`,
        {
          form: {
            collectionId: String(collectionId),
            groupId: String(group.id),
            permissionLevelId: String(levels[0].id),
          },
        },
      );
      expect(res.status()).toBe(409);
      expect((await res.json()).existingGrantId).toBe(existing.id);
    });

    test("the same group can hold grants on two collections", async ({
      page,
    }) => {
      const firstCollection = await createCollection(page, "First Collection");
      const secondCollection = await createCollection(
        page,
        "Second Collection",
      );
      const group = await createGroup(page, "Two-Collection Grantees");
      const levels = await getPermissionLevels(page);

      const first = await createCollectionGrant(
        page,
        firstCollection,
        group.id,
        levels[0].id,
      );
      const second = await createCollectionGrant(
        page,
        secondCollection,
        group.id,
        levels[0].id,
      );
      expect(first.id).not.toBe(second.id);
    });

    test("rejects an unknown collection with 422", async ({ page }) => {
      const group = await createGroup(page, "Bad Collection Target");
      const levels = await getPermissionLevels(page);

      const res = await page.request.post(
        `${baseURL()}/adminPermissions/collectionGrants`,
        {
          form: {
            collectionId: "99999999",
            groupId: String(group.id),
            permissionLevelId: String(levels[0].id),
          },
        },
      );
      expect(res.status()).toBe(422);
    });

    test("updating or deleting a missing grant returns 404", async ({
      page,
    }) => {
      const collectionId = await createCollection(page, "No Grant Collection");
      const group = await createGroup(page, "No Grant Group");
      const levels = await getPermissionLevels(page);

      const update = await page.request.put(
        `${baseURL()}/adminPermissions/collectionGrants/99999999`,
        {
          form: {
            collectionId: String(collectionId),
            groupId: String(group.id),
            permissionLevelId: String(levels[0].id),
          },
        },
      );
      expect(update.status()).toBe(404);

      const remove = await page.request.delete(
        `${baseURL()}/adminPermissions/collectionGrants/99999999`,
        { headers: { Accept: "application/json" } },
      );
      expect(remove.status()).toBe(404);
    });

    test("GET by id is unsupported and returns 405", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/adminPermissions/collectionGrants/1`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(405);
    });
  });
});
