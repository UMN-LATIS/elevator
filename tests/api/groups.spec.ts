import { test, expect, type Page } from "@playwright/test";
import { loginUser, refreshDatabase, baseURL } from "../helpers";

// Endpoints under AdminPermissions.php, routed at /{instance}/adminPermissions.
// All require an admin session (abortUnlessAdmin -> 401 when not).

async function loginAdmin(page: Page) {
  await loginUser(page, "admin");
}

type Group = {
  id: number;
  type: string;
  label: string;
  entries_count: number;
};

// Create a group through the public endpoint and return its parsed record,
// so per-resource tests have a real id to act on.
async function createGroup(
  page: Page,
  form: Record<string, string>,
): Promise<Group> {
  const res = await page.request.post(`${baseURL()}/adminPermissions/groups`, {
    form,
  });
  expect(res.status()).toBe(201);
  return (await res.json()).group as Group;
}

type UserMatch = {
  name: string;
  email: string;
  localUserId: number;
  username: string;
};

// Resolve a real local user id via the autocomplete endpoint, so member
// tests don't depend on hard-coded seed ids.
async function findUserByUsername(
  page: Page,
  username: string,
): Promise<UserMatch> {
  const res = await page.request.get(
    `${baseURL()}/adminPermissions/userAutocomplete?q=${encodeURIComponent(
      username,
    )}`,
    { headers: { Accept: "application/json" } },
  );
  expect(res.status()).toBe(200);
  const { matches } = (await res.json()) as { matches: UserMatch[] };
  const match = matches.find((m) => m.username === username);
  expect(match, `no autocomplete match for "${username}"`).toBeTruthy();
  return match as UserMatch;
}

type GroupEntry = {
  id: number;
  value: string;
};

// Entries only exist on auth-helper group types, and the instance's helper
// may define none (the base AuthHelper is empty, and CI runs with it).
// Discover a type at runtime so entry tests can skip on a bare instance
// instead of failing.
async function findAuthHelperGroupType(page: Page): Promise<string | null> {
  const res = await page.request.get(
    `${baseURL()}/adminPermissions/groupTypes`,
    { headers: { Accept: "application/json" } },
  );
  expect(res.status()).toBe(200);
  const { groupTypes } = (await res.json()) as {
    groupTypes: { type: string }[];
  };
  const globalTypes = ["All", "Authed", "Authed_remote", "User"];
  const isAuthHelperType = (g: { type: string }) =>
    !globalTypes.includes(g.type);
  return groupTypes.find(isAuthHelperType)?.type ?? null;
}

test.describe("adminPermissions", () => {
  test.describe("unauthenticated", () => {
    // Each protected endpoint should reject an anonymous request with 401.
    for (const path of [
      "/adminPermissions/groupTypes",
      "/adminPermissions/permissionLevels",
      "/adminPermissions/groups",
      "/adminPermissions/groups/1",
      "/adminPermissions/groups/1/members",
      "/adminPermissions/userAutocomplete?q=ab",
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
      // Each entry is { type, label, description }; check the type strings.
      const types = groupTypes.map((g: { type: string }) => g.type);
      // The global types are always present regardless of auth helper.
      expect(types).toEqual(
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

    test("creates a whole-population group (All) with no values", async ({
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
      // The group_value scalar is set to 1 server-side so Authed/Remote
      // matching keeps working, but it's a DB-internal detail and is
      // deliberately not part of the JSON contract.
      expect(group.entries_count).toBe(0);
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
  });

  test.describe("GET /userAutocomplete", () => {
    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test("returns matches for a known user", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/adminPermissions/userAutocomplete?q=admin`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(200);

      const { matches } = await res.json();
      expect(Array.isArray(matches)).toBe(true);
      // Each match is reshaped for the new UI: a local id named localUserId.
      const admin = matches.find(
        (m: { username: string }) => m.username === "admin",
      );
      expect(admin).toBeTruthy();
      expect(admin).toHaveProperty("name");
      expect(admin).toHaveProperty("email");
      expect(typeof admin.localUserId).toBe("number");
    });

    test("ignores a query shorter than two characters", async ({ page }) => {
      // The controller short-circuits trivial input before searching.
      const res = await page.request.get(
        `${baseURL()}/adminPermissions/userAutocomplete?q=a`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(200);
      expect((await res.json()).matches).toEqual([]);
    });

    test("rejects a non-GET verb with 405", async ({ page }) => {
      const res = await page.request.post(
        `${baseURL()}/adminPermissions/userAutocomplete`,
        { form: { q: "admin" } },
      );
      expect(res.status()).toBe(405);
    });
  });

  test.describe("GET /groups/{id} (show)", () => {
    test.beforeAll(() => {
      refreshDatabase();
    });

    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test.afterEach(() => {
      refreshDatabase();
    });

    test("returns a single group by id", async ({ page }) => {
      const created = await createGroup(page, {
        type: "All",
        label: "Showable",
      });

      const res = await page.request.get(
        `${baseURL()}/adminPermissions/groups/${created.id}`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(200);

      const { group } = await res.json();
      expect(group.id).toBe(created.id);
      expect(group.label).toBe("Showable");
    });

    test("returns 404 for a missing group", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/adminPermissions/groups/99999999`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(404);
    });

    test("returns 400 for a non-numeric id", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/adminPermissions/groups/not-a-number`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(400);
    });
  });

  test.describe("PATCH/PUT /groups/{id} (update)", () => {
    test.beforeAll(() => {
      refreshDatabase();
    });

    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test.afterEach(() => {
      refreshDatabase();
    });

    test("renames a group, keeping its type", async ({ page }) => {
      const created = await createGroup(page, {
        type: "All",
        label: "Before",
      });

      const res = await page.request.patch(
        `${baseURL()}/adminPermissions/groups/${created.id}`,
        { form: { type: "All", label: "After" } },
      );
      expect(res.status()).toBe(200);

      const { group } = await res.json();
      expect(group.label).toBe("After");
      expect(group.type).toBe("All");
    });

    test("changing the type clears existing members", async ({ page }) => {
      // A User group with a member; switching its type invalidates members.
      const group = await createGroup(page, { type: "User", label: "Movers" });
      const admin = await findUserByUsername(page, "admin");
      await page.request.post(
        `${baseURL()}/adminPermissions/groups/${group.id}/members`,
        { form: { localUserId: String(admin.localUserId) } },
      );

      const res = await page.request.put(
        `${baseURL()}/adminPermissions/groups/${group.id}`,
        { form: { type: "Authed", label: "Movers" } },
      );
      expect(res.status()).toBe(200);

      const { group: updated } = await res.json();
      expect(updated.type).toBe("Authed");
      expect(updated.entries_count).toBe(0);
    });

    test("returns 404 for a missing group", async ({ page }) => {
      const res = await page.request.patch(
        `${baseURL()}/adminPermissions/groups/99999999`,
        { form: { type: "All", label: "Nope" } },
      );
      expect(res.status()).toBe(404);
    });

    test("rejects an invalid label with 422", async ({ page }) => {
      const created = await createGroup(page, {
        type: "All",
        label: "Valid",
      });

      const res = await page.request.patch(
        `${baseURL()}/adminPermissions/groups/${created.id}`,
        { form: { type: "All", label: 'bad <script>' } },
      );
      expect(res.status()).toBe(422);
      expect((await res.json()).errors).toHaveProperty("label");
    });
  });

  test.describe("DELETE /groups/{id} (delete)", () => {
    test.beforeAll(() => {
      refreshDatabase();
    });

    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test.afterEach(() => {
      refreshDatabase();
    });

    test("removes a group, which is then gone", async ({ page }) => {
      const created = await createGroup(page, {
        type: "All",
        label: "Doomed",
      });

      const del = await page.request.delete(
        `${baseURL()}/adminPermissions/groups/${created.id}`,
        { headers: { Accept: "application/json" } },
      );
      expect(del.status()).toBe(200);
      expect((await del.json()).deleted).toBe(created.id);

      const show = await page.request.get(
        `${baseURL()}/adminPermissions/groups/${created.id}`,
        { headers: { Accept: "application/json" } },
      );
      expect(show.status()).toBe(404);
    });

    test("returns 404 for a missing group", async ({ page }) => {
      const res = await page.request.delete(
        `${baseURL()}/adminPermissions/groups/99999999`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(404);
    });
  });

  test.describe("group members", () => {
    test.beforeAll(() => {
      refreshDatabase();
    });

    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test.afterEach(() => {
      refreshDatabase();
    });

    test("adds, lists, and removes a member", async ({ page }) => {
      const group = await createGroup(page, { type: "User", label: "People" });
      const admin = await findUserByUsername(page, "admin");

      const add = await page.request.post(
        `${baseURL()}/adminPermissions/groups/${group.id}/members`,
        { form: { localUserId: String(admin.localUserId) } },
      );
      expect(add.status()).toBe(201);
      expect((await add.json()).member.userId).toBe(admin.localUserId);

      const list = await page.request.get(
        `${baseURL()}/adminPermissions/groups/${group.id}/members`,
        { headers: { Accept: "application/json" } },
      );
      expect(list.status()).toBe(200);
      const { members } = await list.json();
      expect(
        members.some((m: { userId: number }) => m.userId === admin.localUserId),
      ).toBe(true);

      const remove = await page.request.delete(
        `${baseURL()}/adminPermissions/groups/${group.id}/members/${admin.localUserId}`,
        { headers: { Accept: "application/json" } },
      );
      expect(remove.status()).toBe(200);
      expect((await remove.json()).removed).toBe(admin.localUserId);
    });

    test("rejects a duplicate member with 409", async ({ page }) => {
      const group = await createGroup(page, { type: "User", label: "Dupes" });
      const admin = await findUserByUsername(page, "admin");

      const first = await page.request.post(
        `${baseURL()}/adminPermissions/groups/${group.id}/members`,
        { form: { localUserId: String(admin.localUserId) } },
      );
      expect(first.status()).toBe(201);

      const second = await page.request.post(
        `${baseURL()}/adminPermissions/groups/${group.id}/members`,
        { form: { localUserId: String(admin.localUserId) } },
      );
      expect(second.status()).toBe(409);
    });

    test("requires exactly one of localUserId or remoteUserId", async ({
      page,
    }) => {
      const group = await createGroup(page, { type: "User", label: "OneOf" });

      const res = await page.request.post(
        `${baseURL()}/adminPermissions/groups/${group.id}/members`,
        { form: {} },
      );
      expect(res.status()).toBe(422);
    });

    test("only User groups take members", async ({ page }) => {
      const group = await createGroup(page, { type: "All", label: "NoMembers" });

      const res = await page.request.post(
        `${baseURL()}/adminPermissions/groups/${group.id}/members`,
        { form: { localUserId: "1" } },
      );
      expect(res.status()).toBe(422);
    });

    test("removing a non-member returns 404", async ({ page }) => {
      const group = await createGroup(page, { type: "User", label: "Empty" });

      const res = await page.request.delete(
        `${baseURL()}/adminPermissions/groups/${group.id}/members/99999999`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(404);
    });

    test("listing members of a missing group returns 404", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/adminPermissions/groups/99999999/members`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(404);
    });
  });

  test.describe("group entries", () => {
    test.beforeAll(() => {
      refreshDatabase();
    });

    test.beforeEach(async ({ page }) => {
      await loginAdmin(page);
    });

    test.afterEach(() => {
      refreshDatabase();
    });

    test("adds, lists, updates, and removes an entry", async ({ page }) => {
      const type = await findAuthHelperGroupType(page);
      if (type === null) {
        test.skip(true, "the instance's auth helper defines no group types");
        return;
      }

      const group = await createGroup(page, { type, label: "Attributes" });

      const add = await page.request.post(
        `${baseURL()}/adminPermissions/groups/${group.id}/entries`,
        { form: { value: "CSCI.1001" } },
      );
      expect(add.status()).toBe(201);
      const { entry } = (await add.json()) as { entry: GroupEntry };
      expect(entry.value).toBe("CSCI.1001");

      const list = await page.request.get(
        `${baseURL()}/adminPermissions/groups/${group.id}/entries`,
        { headers: { Accept: "application/json" } },
      );
      expect(list.status()).toBe(200);
      const { entries } = (await list.json()) as { entries: GroupEntry[] };
      expect(entries.some((e) => e.id === entry.id)).toBe(true);

      const update = await page.request.put(
        `${baseURL()}/adminPermissions/groups/${group.id}/entries/${entry.id}`,
        { form: { value: "CSCI.2001" } },
      );
      expect(update.status()).toBe(200);
      const updated = (await update.json()) as { entry: GroupEntry };
      expect(updated.entry.value).toBe("CSCI.2001");

      const remove = await page.request.delete(
        `${baseURL()}/adminPermissions/groups/${group.id}/entries/${entry.id}`,
        { headers: { Accept: "application/json" } },
      );
      expect(remove.status()).toBe(200);
      expect((await remove.json()).removed).toBe(entry.id);
    });

    test("only auth-helper group types take entries", async ({ page }) => {
      const group = await createGroup(page, {
        type: "User",
        label: "NoEntries",
      });

      const res = await page.request.post(
        `${baseURL()}/adminPermissions/groups/${group.id}/entries`,
        { form: { value: "anything" } },
      );
      expect(res.status()).toBe(422);
    });

    test("updating or removing a missing entry returns 404", async ({
      page,
    }) => {
      const type = await findAuthHelperGroupType(page);
      if (type === null) {
        test.skip(true, "the instance's auth helper defines no group types");
        return;
      }

      const group = await createGroup(page, { type, label: "NoSuchEntry" });

      const update = await page.request.put(
        `${baseURL()}/adminPermissions/groups/${group.id}/entries/99999999`,
        { form: { value: "anything" } },
      );
      expect(update.status()).toBe(404);

      const remove = await page.request.delete(
        `${baseURL()}/adminPermissions/groups/${group.id}/entries/99999999`,
        { headers: { Accept: "application/json" } },
      );
      expect(remove.status()).toBe(404);
    });

    test("listing entries of a missing group returns 404", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/adminPermissions/groups/99999999/entries`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(404);
    });
  });
});
