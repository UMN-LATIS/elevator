import { test, expect, type Page } from "@playwright/test";
import {
  loginUser,
  createUser,
  baseURL,
  queryDb,
  newLoggedInContext,
  newInstanceAdminContext,
} from "../helpers";

const usersURL = (): string => `${baseURL()}/adminUsers/users`;

const RUN = Date.now().toString(36);
const SEARCH_TOKEN = `us${RUN}`;
const FILTER_TOKEN = `uf${RUN}`;
const PAGE_TOKEN = `up${RUN}`;

type UserRow = {
  id: number;
  username: string;
  displayName: string | null;
  email: string | null;
  emplid: string | null;
  userType: "Local" | "Remote";
  isSuperAdmin: boolean;
  hasExpiry: boolean;
  expires: string | null;
  createdAt: string | null;
  instance: { id: number; name: string } | null;
};

type UserList = {
  users: UserRow[];
  page: number;
  perPage: number;
  total: number;
};

type UserSeed = {
  username: string;
  displayName?: string;
  email?: string;
  emplid?: string;
  userType?: "Local" | "Remote";
  isSuperAdmin?: boolean | null;
};

function insertUsers(seeds: UserSeed[]): void {
  const sqlText = (value: string | undefined) =>
    value === undefined ? "NULL" : `'${value.replace(/'/g, "''")}'`;
  const sqlBoolean = (value: boolean | null) =>
    value === null ? "NULL" : String(value);

  const rows = seeds.map(
    (seed) =>
      `(${sqlText(seed.username)}, ${sqlText(seed.userType ?? "Local")}, ` +
      `${sqlText(seed.displayName)}, ${sqlText(seed.email)}, ` +
      `${sqlText(seed.emplid)}, ${sqlBoolean(seed.isSuperAdmin === undefined ? false : seed.isSuperAdmin)}, ` +
      `false, now())`,
  );

  queryDb(
    `INSERT INTO users (username, usertype, displayname, email, emplid, issuperadmin, hasexpiry, createdat) VALUES ${rows.join(", ")}`,
  );
}

async function listUsers(
  page: Page,
  params: Record<string, string | number> = {},
): Promise<UserList> {
  const res = await page.request.get(usersURL(), {
    params,
    headers: { Accept: "application/json" },
  });
  expect(res.status()).toBe(200);
  return (await res.json()) as UserList;
}

function sortedUsernames(list: UserList): string[] {
  return list.users.map((user) => user.username).sort();
}

test.describe("adminUsers", () => {
  test.beforeAll(() => {
    insertUsers([
      { username: `e2e-${SEARCH_TOKEN}-1`, displayName: `Pat ${SEARCH_TOKEN}NAME` },
      { username: `e2e-${SEARCH_TOKEN}-2`, email: `${SEARCH_TOKEN}MAIL@test.local` },
      { username: `e2e-${SEARCH_TOKEN}-3`, emplid: `${SEARCH_TOKEN}EMPLID` },
      { username: `E2E-${SEARCH_TOKEN.toUpperCase()}-4` },
      { username: `e2e-${SEARCH_TOKEN}-5`, displayName: `a%b${SEARCH_TOKEN}` },
      { username: `e2e-${SEARCH_TOKEN}-6`, displayName: `axb${SEARCH_TOKEN}` },
      { username: `e2e-${SEARCH_TOKEN}-7`, displayName: `c_d${SEARCH_TOKEN}` },
      { username: `e2e-${SEARCH_TOKEN}-8`, displayName: `cxd${SEARCH_TOKEN}` },
    ]);
    insertUsers([
      { username: `e2e-${FILTER_TOKEN}-local` },
      { username: `e2e-${FILTER_TOKEN}-remote`, userType: "Remote" },
      { username: `e2e-${FILTER_TOKEN}-super`, isSuperAdmin: true },
      { username: `e2e-${FILTER_TOKEN}-null`, isSuperAdmin: null },
    ]);
    insertUsers(
      Array.from({ length: 26 }, (_, i) => ({
        username: `e2e-${PAGE_TOKEN}-${String(i + 1).padStart(2, "0")}`,
      })),
    );
  });

  test.describe("access", () => {
    test("signed out gets 401", async ({ page }) => {
      const res = await page.request.get(usersURL(), {
        headers: { Accept: "application/json" },
      });
      expect(res.status()).toBe(401);
    });

    test("a user who is not an admin gets 403", async ({ browser, page }) => {
      const username = `e2e-${RUN}-not-an-admin`;
      await loginUser(page, "admin");
      await createUser(page, username, "password");

      const userPage = await newLoggedInContext(browser, username, "password");
      const res = await userPage.request.get(usersURL(), {
        headers: { Accept: "application/json" },
      });
      expect(res.status()).toBe(403);
    });

    test("an instance admin gets 403", async ({ browser, page }) => {
      await loginUser(page, "admin");
      const instanceAdminPage = await newInstanceAdminContext(
        browser,
        page,
        `e2e-${RUN}-instance-admin`,
        "password",
      );

      const res = await instanceAdminPage.request.get(usersURL(), {
        headers: { Accept: "application/json" },
      });
      expect(res.status()).toBe(403);
    });

    test("a non-GET request gets 405", async ({ page }) => {
      await loginUser(page, "admin");
      const res = await page.request.post(usersURL());
      expect(res.status()).toBe(405);
    });
  });

  test.describe("as a super admin", () => {
    test.beforeEach(async ({ page }) => {
      await loginUser(page, "admin");
    });

    test("rows have the documented shape and never include password", async ({
      page,
    }) => {
      const list = await listUsers(page, { search: `${SEARCH_TOKEN}EMPLID` });

      expect(list.users).toHaveLength(1);
      expect(list.users[0]).toEqual({
        id: expect.any(Number),
        username: `e2e-${SEARCH_TOKEN}-3`,
        displayName: null,
        email: null,
        emplid: `${SEARCH_TOKEN}EMPLID`,
        userType: "Local",
        isSuperAdmin: false,
        hasExpiry: false,
        expires: null,
        createdAt: expect.any(String),
        instance: null,
      });

      const everyone = await listUsers(page);
      for (const user of everyone.users) {
        expect(user).not.toHaveProperty("password");
      }
    });

    test.describe("search", () => {
      const cases = [
        { field: "displayName", search: `${SEARCH_TOKEN}name`, expected: 1 },
        { field: "email", search: `${SEARCH_TOKEN}mail`, expected: 2 },
        { field: "emplid", search: `${SEARCH_TOKEN}emplid`, expected: 3 },
        { field: "username", search: `e2e-${SEARCH_TOKEN}-4`, expected: 4 },
      ];

      for (const { field, search, expected } of cases) {
        test(`matches ${field} regardless of letter case`, async ({ page }) => {
          const list = await listUsers(page, { search });

          expect(list.users.map((user) => user.username.toLowerCase())).toEqual([
            `e2e-${SEARCH_TOKEN}-${expected}`,
          ]);
          expect(list.total).toBe(1);
        });
      }

      test("treats % and _ as literal characters", async ({ page }) => {
        const percent = await listUsers(page, { search: `a%b${SEARCH_TOKEN}` });
        expect(sortedUsernames(percent)).toEqual([`e2e-${SEARCH_TOKEN}-5`]);

        const underscore = await listUsers(page, { search: `c_d${SEARCH_TOKEN}` });
        expect(sortedUsernames(underscore)).toEqual([`e2e-${SEARCH_TOKEN}-7`]);
      });

      test("ignores a whitespace-only search", async ({ page }) => {
        const blank = await listUsers(page, { search: "   " });
        const unsearched = await listUsers(page);

        expect(blank.total).toBe(unsearched.total);
      });
    });

    test.describe("filters", () => {
      test("userType=Remote returns only Remote users", async ({ page }) => {
        const list = await listUsers(page, {
          search: `e2e-${FILTER_TOKEN}`,
          userType: "Remote",
        });

        expect(sortedUsernames(list)).toEqual([`e2e-${FILTER_TOKEN}-remote`]);
        expect(list.total).toBe(1);
      });

      test("isSuperAdmin=true returns only super admins", async ({ page }) => {
        const list = await listUsers(page, {
          search: `e2e-${FILTER_TOKEN}`,
          isSuperAdmin: "true",
        });

        expect(sortedUsernames(list)).toEqual([`e2e-${FILTER_TOKEN}-super`]);
      });

      test("isSuperAdmin=false includes rows where the column is NULL", async ({
        page,
      }) => {
        const list = await listUsers(page, {
          search: `e2e-${FILTER_TOKEN}`,
          isSuperAdmin: "false",
        });

        expect(sortedUsernames(list)).toEqual(
          [
            `e2e-${FILTER_TOKEN}-local`,
            `e2e-${FILTER_TOKEN}-null`,
            `e2e-${FILTER_TOKEN}-remote`,
          ].sort(),
        );
        expect(list.total).toBe(3);
      });

      test("filters and search combine with AND", async ({ page }) => {
        const list = await listUsers(page, {
          search: `e2e-${FILTER_TOKEN}`,
          userType: "Local",
          isSuperAdmin: "false",
        });

        expect(sortedUsernames(list)).toEqual(
          [`e2e-${FILTER_TOKEN}-local`, `e2e-${FILTER_TOKEN}-null`].sort(),
        );
        expect(list.total).toBe(2);
      });

      test("an unrecognized filter value gets 422 naming the parameter", async ({
        page,
      }) => {
        const res = await page.request.get(usersURL(), {
          params: { userType: "Guest", isSuperAdmin: "yes" },
          headers: { Accept: "application/json" },
        });

        expect(res.status()).toBe(422);
        const body = (await res.json()) as { errors: Record<string, string[]> };
        expect(Object.keys(body.errors).sort()).toEqual(["isSuperAdmin", "userType"]);
      });
    });

    test.describe("pagination", () => {
      test("pages split the results newest first without overlap", async ({
        page,
      }) => {
        const search = `e2e-${PAGE_TOKEN}`;
        const first = await listUsers(page, { search, perPage: 25, page: 1 });
        const second = await listUsers(page, { search, perPage: 25, page: 2 });

        expect(first).toMatchObject({ page: 1, perPage: 25, total: 26 });
        expect(second).toMatchObject({ page: 2, perPage: 25, total: 26 });
        expect(first.users).toHaveLength(25);
        expect(second.users).toHaveLength(1);

        const ids = [...first.users, ...second.users].map((user) => user.id);
        expect(ids).toEqual([...ids].sort((a, b) => b - a));
        expect(new Set(ids).size).toBe(26);
      });

      test("a page past the end is empty but keeps the total", async ({ page }) => {
        const list = await listUsers(page, {
          search: `e2e-${PAGE_TOKEN}`,
          perPage: 25,
          page: 3,
        });

        expect(list.users).toEqual([]);
        expect(list.total).toBe(26);

        const largestPage = await listUsers(page, {
          search: `e2e-${PAGE_TOKEN}`,
          page: "9223372036854775807",
        });
        expect(largestPage.users).toEqual([]);
        expect(largestPage.total).toBe(26);
      });

      test("invalid page and perPage fall back to page 1 and 25", async ({
        page,
      }) => {
        const list = await listUsers(page, {
          search: `e2e-${PAGE_TOKEN}`,
          perPage: 7,
          page: "abc",
        });

        expect(list).toMatchObject({ page: 1, perPage: 25, total: 26 });
        expect(list.users).toHaveLength(25);
      });
    });
  });
});
