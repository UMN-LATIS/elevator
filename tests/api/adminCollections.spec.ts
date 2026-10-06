import { test, expect, type APIResponse, type Page } from "@playwright/test";
import { loginUser, createUser, refreshDatabase, baseURL } from "../helpers";

// Endpoints under AdminCollections.php, the JSON collection CRUD for the
// new admin UI. Every verb reads a form-encoded body and requires an
// instance admin session.

const collectionsURL = (): string =>
  `${baseURL()}/adminCollections/collections`;

// Collection::jsonSerialize(), the shape list items use.
type CollectionListItem = {
  id: number;
  title: string;
  parentId: number | null;
  showInBrowse: boolean;
  previewImageId: string | null;
};

// The detail shape adds the admin-only settings the list omits.
type CollectionDetail = CollectionListItem & {
  description: string | null;
  bucket: string | null;
  bucketRegion: string | null;
  s3Key: string | null;
  s3Secret: string | null;
};

async function loginAdmin(page: Page): Promise<void> {
  await loginUser(page, "admin");
}

async function collectionFrom(res: APIResponse): Promise<CollectionDetail> {
  return (await res.json()).collection as CollectionDetail;
}

async function createCollection(
  page: Page,
  form: Record<string, string>,
): Promise<CollectionDetail> {
  const res = await page.request.post(collectionsURL(), { form });
  expect(res.status()).toBe(201);
  return collectionFrom(res);
}

async function getCollection(
  page: Page,
  collectionId: number,
): Promise<CollectionDetail> {
  const res = await page.request.get(`${collectionsURL()}/${collectionId}`, {
    headers: { Accept: "application/json" },
  });
  expect(res.status()).toBe(200);
  return collectionFrom(res);
}

test.describe("adminCollections", () => {
  test.describe("unauthenticated", () => {
    const requests = [
      { method: "GET", path: "/adminCollections/collections" },
      { method: "POST", path: "/adminCollections/collections" },
      { method: "GET", path: "/adminCollections/collections/1" },
      { method: "PUT", path: "/adminCollections/collections/1" },
      { method: "DELETE", path: "/adminCollections/collections/1" },
    ] as const;

    for (const { method, path } of requests) {
      test(`${method} ${path} returns 401`, async ({ page }) => {
        const res = await page.request.fetch(`${baseURL()}${path}`, {
          method,
          headers: { Accept: "application/json" },
        });
        expect(res.status()).toBe(401);
      });
    }
  });

  test.describe("as a non-admin user", () => {
    const NON_ADMIN = {
      username: "e2e-not-an-admin",
      password: "e2e-not-an-admin",
    };

    // reset-test-db.sh never truncates users, so find-or-create survives
    // previous runs
    test.beforeAll(async ({ browser }) => {
      const context = await browser.newContext();
      const page = await context.newPage();
      await loginAdmin(page);
      await createUser(page, NON_ADMIN.username, NON_ADMIN.password);
      await context.close();
    });

    test.beforeEach(async ({ page }) => {
      await loginUser(page, NON_ADMIN.username, NON_ADMIN.password);
    });

    test("GET collections returns 403", async ({ page }) => {
      const res = await page.request.get(collectionsURL(), {
        headers: { Accept: "application/json" },
      });
      expect(res.status()).toBe(403);
    });

    test("POST collections returns 403", async ({ page }) => {
      const res = await page.request.post(collectionsURL(), {
        form: { title: "Should Not Exist" },
      });
      expect(res.status()).toBe(403);
    });
  });

  test.describe("as an admin", () => {
    test.beforeAll(() => {
      refreshDatabase();
    });

    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test.afterEach(() => {
      refreshDatabase();
    });

    test("creates a collection with defaults from a title alone", async ({
      page,
    }) => {
      const collection = await createCollection(page, {
        title: "Bare Minimum",
      });

      expect(collection.id).toBeGreaterThan(0);
      expect(collection.title).toBe("Bare Minimum");
      expect(collection.parentId).toBeNull();
      expect(collection.showInBrowse).toBe(true);
      expect(collection.description).toBeNull();
    });

    test("stores showInBrowse false from the string form value", async ({
      page,
    }) => {
      const collection = await createCollection(page, {
        title: "Hidden From Browse",
        showInBrowse: "false",
      });
      expect(collection.showInBrowse).toBe(false);
    });

    test("stores explicit S3 overrides", async ({ page }) => {
      const collection = await createCollection(page, {
        title: "Custom S3",
        bucket: "custom-bucket",
        bucketRegion: "us-test-1",
        s3Key: "custom-key",
        s3Secret: "custom-secret",
      });

      expect(collection.bucket).toBe("custom-bucket");
      expect(collection.bucketRegion).toBe("us-test-1");
      expect(collection.s3Key).toBe("custom-key");
      expect(collection.s3Secret).toBe("custom-secret");
    });

    test("blank S3 fields fall back to instance defaults like omitted ones", async ({
      page,
    }) => {
      const omitted = await createCollection(page, { title: "Omitted S3" });
      const blank = await createCollection(page, {
        title: "Blank S3",
        bucket: "",
        bucketRegion: "",
        s3Key: "",
        s3Secret: "",
      });

      expect(blank.bucket).toBe(omitted.bucket);
      expect(blank.bucketRegion).toBe(omitted.bucketRegion);
      expect(blank.s3Key).toBe(omitted.s3Key);
      expect(blank.s3Secret).toBe(omitted.s3Secret);
    });

    test("lists collections without S3 settings", async ({ page }) => {
      const created = await createCollection(page, {
        title: "Listed Collection",
        s3Key: "leaky-key",
        s3Secret: "leaky-secret",
      });

      const res = await page.request.get(collectionsURL(), {
        headers: { Accept: "application/json" },
      });
      expect(res.status()).toBe(200);
      const { collections } = (await res.json()) as {
        collections: CollectionListItem[];
      };

      const item = collections.find(
        (candidate) => candidate.id === created.id,
      );
      expect(item).toBeDefined();
      expect(item?.title).toBe("Listed Collection");
      expect(item?.showInBrowse).toBe(true);
      expect(item).not.toHaveProperty("s3Key");
      expect(item).not.toHaveProperty("s3Secret");
      expect(item).not.toHaveProperty("bucket");
    });

    test("returns the detail shape for a single collection", async ({
      page,
    }) => {
      const created = await createCollection(page, {
        title: "Detailed",
        description: "A described collection",
      });

      const collection = await getCollection(page, created.id);
      expect(collection.id).toBe(created.id);
      expect(collection.title).toBe("Detailed");
      expect(collection.description).toBe("A described collection");
    });

    test("rejects a missing title with 422 on create and update", async ({
      page,
    }) => {
      const create = await page.request.post(collectionsURL(), {
        form: { description: "No title here" },
      });
      expect(create.status()).toBe(422);
      expect((await create.json()).errors).toHaveProperty("title");

      const existing = await createCollection(page, { title: "Needs Title" });
      const update = await page.request.put(
        `${collectionsURL()}/${existing.id}`,
        { form: { description: "still no title" } },
      );
      expect(update.status()).toBe(422);
      expect((await update.json()).errors).toHaveProperty("title");
    });

    test("an unknown parentId returns 422 on create and update", async ({
      page,
    }) => {
      const create = await page.request.post(collectionsURL(), {
        form: { title: "Orphan", parentId: "99999999" },
      });
      expect(create.status()).toBe(422);

      const existing = await createCollection(page, { title: "Movable" });
      const update = await page.request.put(
        `${collectionsURL()}/${existing.id}`,
        { form: { title: "Movable", parentId: "99999999" } },
      );
      expect(update.status()).toBe(422);
    });

    test("creates a child under a parent and parentId 0 moves it to the top", async ({
      page,
    }) => {
      const parent = await createCollection(page, { title: "Parent" });
      const child = await createCollection(page, {
        title: "Child",
        parentId: String(parent.id),
      });
      expect(child.parentId).toBe(parent.id);

      const res = await page.request.put(`${collectionsURL()}/${child.id}`, {
        form: { title: "Child", parentId: "0" },
      });
      expect(res.status()).toBe(200);
      expect((await collectionFrom(res)).parentId).toBeNull();
    });

    test("updates only the fields present in the body", async ({ page }) => {
      const created = await createCollection(page, {
        title: "Original Title",
        description: "Original description",
        bucket: "original-bucket",
      });

      const res = await page.request.put(`${collectionsURL()}/${created.id}`, {
        form: { title: "New Title" },
      });
      expect(res.status()).toBe(200);
      const updated = await collectionFrom(res);

      expect(updated.title).toBe("New Title");
      expect(updated.description).toBe("Original description");
      expect(updated.bucket).toBe("original-bucket");
    });

    test("PATCH updates like PUT", async ({ page }) => {
      const created = await createCollection(page, { title: "Patch Me" });

      const res = await page.request.patch(
        `${collectionsURL()}/${created.id}`,
        { form: { title: "Patched", showInBrowse: "false" } },
      );
      expect(res.status()).toBe(200);
      const updated = await collectionFrom(res);
      expect(updated.title).toBe("Patched");
      expect(updated.showInBrowse).toBe(false);
    });

    test("a blank S3 field keeps the stored value on update", async ({
      page,
    }) => {
      const created = await createCollection(page, {
        title: "Keeps S3",
        bucket: "original-bucket",
        s3Key: "original-key",
      });

      const blanked = await page.request.put(
        `${collectionsURL()}/${created.id}`,
        { form: { title: "Keeps S3", bucket: "", s3Key: "" } },
      );
      expect(blanked.status()).toBe(200);
      const afterBlank = await collectionFrom(blanked);
      expect(afterBlank.bucket).toBe("original-bucket");
      expect(afterBlank.s3Key).toBe("original-key");

      const replaced = await page.request.put(
        `${collectionsURL()}/${created.id}`,
        { form: { title: "Keeps S3", bucket: "replacement-bucket" } },
      );
      expect(replaced.status()).toBe(200);
      expect((await collectionFrom(replaced)).bucket).toBe(
        "replacement-bucket",
      );
    });

    test("clears the description with an empty string", async ({ page }) => {
      const created = await createCollection(page, {
        title: "Described",
        description: "Something",
      });

      const res = await page.request.put(`${collectionsURL()}/${created.id}`, {
        form: { title: "Described", description: "" },
      });
      expect(res.status()).toBe(200);
      expect((await collectionFrom(res)).description).toBe("");
    });

    test("rejects a parent that is the collection itself or a descendant", async ({
      page,
    }) => {
      const top = await createCollection(page, { title: "Top" });
      const middle = await createCollection(page, {
        title: "Middle",
        parentId: String(top.id),
      });
      const bottom = await createCollection(page, {
        title: "Bottom",
        parentId: String(middle.id),
      });

      const ontoItself = await page.request.put(
        `${collectionsURL()}/${top.id}`,
        { form: { title: "Top", parentId: String(top.id) } },
      );
      expect(ontoItself.status()).toBe(422);

      const ontoGrandchild = await page.request.put(
        `${collectionsURL()}/${top.id}`,
        { form: { title: "Top", parentId: String(bottom.id) } },
      );
      expect(ontoGrandchild.status()).toBe(422);

      // moving up the chain is not a cycle, so it still works
      const ontoGrandparent = await page.request.put(
        `${collectionsURL()}/${bottom.id}`,
        { form: { title: "Bottom", parentId: String(top.id) } },
      );
      expect(ontoGrandparent.status()).toBe(200);
      expect((await collectionFrom(ontoGrandparent)).parentId).toBe(top.id);
    });

    test("deletes a collection and moves its children to the top level", async ({
      page,
    }) => {
      const parent = await createCollection(page, { title: "Doomed Parent" });
      const child = await createCollection(page, {
        title: "Surviving Child",
        parentId: String(parent.id),
      });

      const res = await page.request.delete(
        `${collectionsURL()}/${parent.id}`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(200);
      expect((await res.json()).deleted).toBe(parent.id);

      const gone = await page.request.get(`${collectionsURL()}/${parent.id}`, {
        headers: { Accept: "application/json" },
      });
      expect(gone.status()).toBe(404);

      const survivor = await getCollection(page, child.id);
      expect(survivor.parentId).toBeNull();
    });

    test("fetching, updating, or deleting a missing collection returns 404", async ({
      page,
    }) => {
      const missing = `${collectionsURL()}/99999999`;

      const get = await page.request.get(missing, {
        headers: { Accept: "application/json" },
      });
      expect(get.status()).toBe(404);

      const put = await page.request.put(missing, {
        form: { title: "Ghost" },
      });
      expect(put.status()).toBe(404);

      const del = await page.request.delete(missing, {
        headers: { Accept: "application/json" },
      });
      expect(del.status()).toBe(404);
    });

    test("returns 400 for a non-numeric id", async ({ page }) => {
      const res = await page.request.get(
        `${collectionsURL()}/not-a-number`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(400);
    });

    test("DELETE on the list route returns 405", async ({ page }) => {
      const res = await page.request.delete(collectionsURL(), {
        headers: { Accept: "application/json" },
      });
      expect(res.status()).toBe(405);
    });
  });
});
