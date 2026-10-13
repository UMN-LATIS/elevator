import { test, expect, type Browser, type Page } from "@playwright/test";
import { execSync } from "child_process";
import path from "path";
import {
  loginUser,
  createUser,
  createCollection,
  refreshDatabase,
  baseURL,
} from "../helpers";

const projectRoot = path.resolve(__dirname, "../..");

const PERM_ADMIN = 60;

// LEAKED_ENV_KEY must be a key in SERVER_ENV_PATH, or the
// file handling tests pass even when processCSV reads any
// server file.
const SERVER_ENV_PATH = "/var/www/html/.env";
const LEAKED_ENV_KEY = "DATABASE_PASSWORD=";

interface ImportFixture {
  templateId: number;
  titleField: string;
  collectionId: number;
}

function queryDb(sql: string): string {
  return execSync(
    `docker compose exec -T postgres psql -U elevator -d elevator -tA -c "${sql}"`,
    { cwd: projectRoot, encoding: "utf8" },
  ).trim();
}

function countCsvBatches(): number {
  return Number(queryDb("SELECT count(*) FROM csv_batches"));
}

function countAssetsIn(collectionId: number): number {
  return Number(
    queryDb(
      `SELECT count(*) FROM assets WHERE collectionid = ${collectionId} AND assetid IS NOT NULL`,
    ),
  );
}

// A parentObject save leaves a text field's widgets as they
// were and changes only modifiedby, so compare both.
function assetState(objectId: string): string {
  return queryDb(
    `SELECT modifiedby, widgets FROM assets WHERE assetid = '${objectId}'`,
  );
}

function newestAssetIdIn(collectionId: number): string {
  const id = queryDb(
    `SELECT assetid FROM assets WHERE collectionid = ${collectionId} AND assetid IS NOT NULL ORDER BY id DESC LIMIT 1`,
  );
  expect(id, `collection ${collectionId} should hold an asset`).not.toBe("");
  return id;
}

async function createImportFixture(admin: Page): Promise<ImportFixture> {
  const res = await admin.request.post(`${baseURL()}/templates/update`, {
    headers: { Accept: "application/json" },
    form: {
      name: "CSV Import Template",
      templateColor: "0",
      recursiveIndexDepth: "1",
      collectionPosition: "0",
      templatePosition: "0",
      "widget[0][label]": "Title",
      "widget[0][fieldTitle]": "title",
      "widget[0][fieldType]": "1",
      "widget[0][viewOrder]": "1",
      "widget[0][templateOrder]": "1",
      "widget[0][fieldData]": "",
      "widget[0][clickToSearchType]": "0",
      "widget[0][tooltip]": "",
      "widget[0][display]": "On",
      // processCSV echoes preview titles. With this off,
      // the file handling tests can't see a file's lines.
      "widget[0][displayInPreview]": "On",
    },
  });
  expect(res.status()).toBe(200);
  const template = (await res.json()) as {
    id: number;
    widgetArray: { fieldTitle: string }[];
  };
  const collectionId = await createCollection(admin, "Import Collection");
  return {
    templateId: template.id,
    titleField: template.widgetArray[0].fieldTitle,
    collectionId,
  };
}

async function newLoggedInPage(
  browser: Browser,
  username: string,
  password: string,
): Promise<Page> {
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();
  await loginUser(page, username, password);
  return page;
}

async function newAnonymousPage(browser: Browser): Promise<Page> {
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  return context.newPage();
}

function userIdInDb(username: string): string {
  const id = queryDb(`SELECT id FROM users WHERE username = '${username}'`);
  expect(id, `user ${username} should exist`).not.toBe("");
  return id;
}

// Below PERM_ADMIN, importFromCSV refuses the upload, so
// tests using this user would never reach processCSV.
async function newCollectionAdminPage(
  browser: Browser,
  superAdminPage: Page,
  username: string,
  password: string,
  collectionId: number,
): Promise<Page> {
  await createUser(superAdminPage, username, password, { isSuperAdmin: false });

  const groupResponse = await superAdminPage.request.post(
    `${baseURL()}/adminPermissions/groups`,
    { form: { type: "User", label: `Collection admins ${username}` } },
  );
  expect(groupResponse.status()).toBe(201);
  const groupId = (await groupResponse.json()).group.id as number;

  const memberResponse = await superAdminPage.request.post(
    `${baseURL()}/adminPermissions/groups/${groupId}/members`,
    { form: { localUserId: userIdInDb(username) } },
  );
  expect(memberResponse.status()).toBe(201);

  const levelsResponse = await superAdminPage.request.get(
    `${baseURL()}/adminPermissions/permissionLevels`,
    { headers: { Accept: "application/json" } },
  );
  const { permissionLevels } = (await levelsResponse.json()) as {
    permissionLevels: { id: number; level: number }[];
  };
  const adminLevel = permissionLevels.find((l) => l.level === PERM_ADMIN);
  expect(
    adminLevel,
    "seed should include the admin permission level",
  ).toBeDefined();

  const grantResponse = await superAdminPage.request.post(
    `${baseURL()}/adminPermissions/collectionGrants`,
    {
      form: {
        collectionId: String(collectionId),
        groupId: String(groupId),
        permissionLevelId: String(adminLevel!.id),
      },
    },
  );
  expect(grantResponse.status()).toBe(201);

  return newLoggedInPage(browser, username, password);
}

// CodeIgniter's uploader numbers a repeated filename and
// refuses it after 99 copies, so a fixed name fails.
function uniqueUploadName(): string {
  return `import-${Date.now()}-${Math.random().toString(36).slice(2)}.csv`;
}

async function uploadCsv(
  page: Page,
  fixture: ImportFixture,
  csv: string,
): Promise<string> {
  const res = await page.request.post(
    `${baseURL()}/assetManager/importFromCSV`,
    {
      multipart: {
        templateId: String(fixture.templateId),
        userfile: {
          name: uniqueUploadName(),
          mimeType: "text/csv",
          buffer: Buffer.from(csv),
        },
      },
    },
  );
  expect(res.ok(), "importFromCSV should accept the upload").toBe(true);
  const match = (await res.text()).match(/name="filename" value="([^"]+)"/);
  expect(
    match,
    "mapping form should carry the uploaded filename",
  ).not.toBeNull();
  return match![1];
}

async function processCsv(
  page: Page,
  fixture: ImportFixture,
  fields: {
    filename: string;
    collectionId?: number | string;
    mapping: string[];
    parentObject?: string;
    targetFieldSelect?: string;
  },
) {
  const form: Record<string, string> = {
    filename: fields.filename,
    templateId: String(fixture.templateId),
    collectionId: String(fields.collectionId ?? fixture.collectionId),
  };
  fields.mapping.forEach((fieldTitle, i) => {
    form[`targetField[${i}]`] = fieldTitle;
    form[`delimiter[${i}]`] = "";
  });
  if (fields.parentObject) {
    form.parentObject = fields.parentObject;
    form.targetFieldSelect = fields.targetFieldSelect ?? "";
  }
  return page.request.post(`${baseURL()}/assetManager/processCSV`, { form });
}

// Without the extra "ignore" columns, a .env line with
// commas hits a fatal error before any title is echoed, so
// the file handling tests pass even when processCSV reads
// a server file.
const serverFileMapping = (fixture: ImportFixture) => [
  fixture.titleField,
  "ignore",
  "ignore",
  "ignore",
  "ignore",
];

test.describe("AssetManager::processCSV file handling", () => {
  test.beforeEach(() => {
    refreshDatabase();
  });

  test("an anonymous caller cannot import a server file", async ({
    browser,
  }) => {
    const admin = await newLoggedInPage(browser, "admin", "admin");
    const fixture = await createImportFixture(admin);
    const page = await newAnonymousPage(browser);

    const res = await processCsv(page, fixture, {
      filename: SERVER_ENV_PATH,
      mapping: serverFileMapping(fixture),
    });

    expect(await res.text()).not.toContain(LEAKED_ENV_KEY);
    expect(countCsvBatches()).toBe(0);
  });

  test("an admin cannot import a file they did not upload", async ({
    browser,
  }) => {
    const admin = await newLoggedInPage(browser, "admin", "admin");
    const fixture = await createImportFixture(admin);

    const res = await processCsv(admin, fixture, {
      filename: SERVER_ENV_PATH,
      mapping: serverFileMapping(fixture),
    });

    expect(await res.text()).not.toContain(LEAKED_ENV_KEY);
    expect(countCsvBatches()).toBe(0);
  });
});

test.describe("AssetManager::processCSV collection permissions", () => {
  test.beforeEach(() => {
    refreshDatabase();
  });

  async function setUp(browser: Browser) {
    const admin = await newLoggedInPage(browser, "admin", "admin");
    const fixture = await createImportFixture(admin);
    const carolCollectionId = await createCollection(
      admin,
      "Carol's Collection",
    );

    const victimFile = await uploadCsv(
      admin,
      fixture,
      "Title\nOriginal title\n",
    );
    await processCsv(admin, fixture, {
      filename: victimFile,
      mapping: [fixture.titleField],
    });
    const victimId = newestAssetIdIn(fixture.collectionId);

    const carol = await newCollectionAdminPage(
      browser,
      admin,
      `carol_${Date.now()}`,
      "carol-pw",
      carolCollectionId,
    );
    return { carol, fixture, carolCollectionId, victimId };
  }

  test("a collection admin can import into their own collection", async ({
    browser,
  }) => {
    const { carol, fixture, carolCollectionId } = await setUp(browser);

    const filename = await uploadCsv(carol, fixture, "Title\nMine\n");
    await processCsv(carol, fixture, {
      filename,
      collectionId: carolCollectionId,
      mapping: [fixture.titleField],
    });

    expect(countAssetsIn(carolCollectionId)).toBe(1);
  });

  test("a collection admin cannot import into a collection they have no grant on", async ({
    browser,
  }) => {
    const { carol, fixture } = await setUp(browser);
    const batchesBefore = countCsvBatches();
    const assetsBefore = countAssetsIn(fixture.collectionId);

    const filename = await uploadCsv(carol, fixture, "Title\nPlanted\n");
    await processCsv(carol, fixture, {
      filename,
      mapping: [fixture.titleField],
    });

    expect(countAssetsIn(fixture.collectionId)).toBe(assetsBefore);
    expect(countCsvBatches()).toBe(batchesBefore);
  });

  test("update mode cannot overwrite an asset outside the caller's collections", async ({
    browser,
  }) => {
    const { carol, fixture, carolCollectionId, victimId } =
      await setUp(browser);
    const stateBefore = assetState(victimId);

    const filename = await uploadCsv(
      carol,
      fixture,
      `Id,Title\n${victimId},Overwritten\n`,
    );
    await processCsv(carol, fixture, {
      filename,
      collectionId: carolCollectionId,
      mapping: ["objectId", fixture.titleField],
    });

    expect(assetState(victimId)).toBe(stateBefore);
  });

  test("a parent object in the caller's own collection is updated", async ({
    browser,
  }) => {
    const { carol, fixture, carolCollectionId } = await setUp(browser);
    const parentFile = await uploadCsv(carol, fixture, "Title\nParent\n");
    await processCsv(carol, fixture, {
      filename: parentFile,
      collectionId: carolCollectionId,
      mapping: [fixture.titleField],
    });
    const parentId = newestAssetIdIn(carolCollectionId);

    const filename = await uploadCsv(carol, fixture, "Title\nChild\n");
    const res = await processCsv(carol, fixture, {
      filename,
      collectionId: carolCollectionId,
      mapping: [fixture.titleField],
      parentObject: parentId,
      targetFieldSelect: fixture.titleField,
    });

    const body = await res.text();
    expect(body).toContain("Updated parent:");
    expect(body).toContain(parentId);
  });

  test("a parent object outside the caller's collections is not modified", async ({
    browser,
  }) => {
    const { carol, fixture, carolCollectionId, victimId } =
      await setUp(browser);
    const stateBefore = assetState(victimId);

    const filename = await uploadCsv(carol, fixture, "Title\nChild\n");
    await processCsv(carol, fixture, {
      filename,
      collectionId: carolCollectionId,
      mapping: [fixture.titleField],
      parentObject: victimId,
      targetFieldSelect: fixture.titleField,
    });

    expect(assetState(victimId)).toBe(stateBefore);
  });
});

test.describe("AssetManager CSV import", () => {
  test.beforeEach(() => {
    refreshDatabase();
  });

  test("a mapping to a field the template does not have is rejected", async ({
    browser,
  }) => {
    const admin = await newLoggedInPage(browser, "admin", "admin");
    const fixture = await createImportFixture(admin);

    const filename = await uploadCsv(admin, fixture, "Title\nFirst\n");
    const res = await processCsv(admin, fixture, {
      filename,
      mapping: ["not_a_field"],
    });

    expect(res.status()).toBe(422);
    expect(countCsvBatches()).toBe(0);
  });

  test("a column key that is not an integer is rejected", async ({
    browser,
  }) => {
    const admin = await newLoggedInPage(browser, "admin", "admin");
    const fixture = await createImportFixture(admin);
    const filename = await uploadCsv(admin, fixture, "Title\nFirst\n");

    const res = await admin.request.post(
      `${baseURL()}/assetManager/processCSV`,
      {
        form: {
          filename,
          templateId: String(fixture.templateId),
          collectionId: String(fixture.collectionId),
          "targetField[foo]": fixture.titleField,
          "delimiter[foo]": "",
        },
      },
    );

    expect(res.status()).toBe(422);
    expect(countCsvBatches()).toBe(0);
  });

  test("an import without a chosen collection says which field is wrong", async ({
    browser,
  }) => {
    const admin = await newLoggedInPage(browser, "admin", "admin");
    const fixture = await createImportFixture(admin);
    const filename = await uploadCsv(admin, fixture, "Title\nFirst\n");

    const res = await processCsv(admin, fixture, {
      filename,
      collectionId: "---",
      mapping: [fixture.titleField],
    });

    expect(res.status()).toBe(422);
    expect(await res.text()).toContain("collectionId");
  });

  test("a template outside the instance is refused at upload", async ({
    browser,
  }) => {
    const admin = await newLoggedInPage(browser, "admin", "admin");
    const fixture = await createImportFixture(admin);
    queryDb(
      `DELETE FROM instance_templates WHERE template_id = ${fixture.templateId}`,
    );

    const res = await admin.request.post(
      `${baseURL()}/assetManager/importFromCSV`,
      {
        multipart: {
          templateId: String(fixture.templateId),
          userfile: {
            name: uniqueUploadName(),
            mimeType: "text/csv",
            buffer: Buffer.from("Title\nFirst\n"),
          },
        },
      },
    );

    expect(await res.text()).not.toContain('name="filename"');
  });

  test("the first of two uploads can still be imported", async ({
    browser,
  }) => {
    const admin = await newLoggedInPage(browser, "admin", "admin");
    const fixture = await createImportFixture(admin);
    const firstFile = await uploadCsv(admin, fixture, "Title\nFirst\n");
    await uploadCsv(admin, fixture, "Title\nSecond\n");

    const res = await processCsv(admin, fixture, {
      filename: firstFile,
      mapping: [fixture.titleField],
    });

    expect(res.ok()).toBe(true);
    expect(countAssetsIn(fixture.collectionId)).toBe(1);
  });

  test("an update row whose collection no longer exists is refused cleanly", async ({
    browser,
  }) => {
    const admin = await newLoggedInPage(browser, "admin", "admin");
    const fixture = await createImportFixture(admin);
    const seedFile = await uploadCsv(admin, fixture, "Title\nOriginal\n");
    await processCsv(admin, fixture, {
      filename: seedFile,
      mapping: [fixture.titleField],
    });
    const assetId = newestAssetIdIn(fixture.collectionId);
    queryDb(
      `UPDATE assets SET collectionid = 999999 WHERE assetid = '${assetId}'`,
    );

    const filename = await uploadCsv(
      admin,
      fixture,
      `Id,Title\n${assetId},Overwritten\n`,
    );
    const res = await processCsv(admin, fixture, {
      filename,
      mapping: ["objectId", fixture.titleField],
    });

    expect(await res.text()).toContain("You do not have permission");
  });

  test("an admin can import a CSV they uploaded", async ({ browser }) => {
    const admin = await newLoggedInPage(browser, "admin", "admin");
    const fixture = await createImportFixture(admin);

    const filename = await uploadCsv(
      admin,
      fixture,
      "Title\nFirst\nSecond\nThird\n",
    );
    const res = await processCsv(admin, fixture, {
      filename,
      mapping: [fixture.titleField],
    });

    expect(res.ok()).toBe(true);
    expect(countAssetsIn(fixture.collectionId)).toBe(3);
  });

  test("an import over 400 rows continues across the redirect", async ({
    browser,
  }) => {
    test.setTimeout(300_000);
    const admin = await newLoggedInPage(browser, "admin", "admin");
    const fixture = await createImportFixture(admin);
    const rows = Array.from({ length: 401 }, (_, i) => `Row ${i + 1}`);

    const filename = await uploadCsv(
      admin,
      fixture,
      `Title\n${rows.join("\n")}\n`,
    );
    const res = await processCsv(admin, fixture, {
      filename,
      mapping: [fixture.titleField],
    });

    expect(res.ok()).toBe(true);
    expect(res.url()).toContain("/assetManager/processCSV/");
    expect(countAssetsIn(fixture.collectionId)).toBe(401);
  });
});
