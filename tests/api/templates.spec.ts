import { test, expect, type Page } from "@playwright/test";
import {
  loginUser,
  refreshDatabase,
  // Aliased to avoid conflict with the richer local createTemplate below,
  // which tests the widgetArray/fieldTitle surface of the new editor API.
  createTemplate as createBasicTemplate,
  baseURL,
} from "../helpers";

// ─── Types (richer API surface) ───────────────────────────────────────────────

interface WidgetShape {
  widgetId: number;
  fieldTitle: string;
  label: string;
  tooltip: unknown;
  templateOrder: number;
  viewOrder: number;
  display: boolean;
  displayInPreview: boolean;
  required: boolean;
  searchable: boolean;
  allowMultiple: boolean;
  attemptAutocomplete: boolean;
  directSearch: boolean;
  clickToSearch: boolean;
  clickToSearchType: number;
  fieldData: unknown;
  fieldType: string;
  fieldTypeId: number;
}

interface TemplateShape {
  id: number;
  name: string;
  createdAt: string;
  modifiedAt: string;
  showCollection: boolean;
  showTemplate: boolean;
  includeInSearch: boolean;
  indexForSearching: boolean;
  isHidden: boolean;
  templateColor: number;
  recursiveIndexDepth: number;
  widgetArray: WidgetShape[];
}

// ─── Helpers (richer API surface) ─────────────────────────────────────────────

// Required NOT NULL fields that update() does not default itself when the POST
// key is absent (it calls setX(false) which Doctrine persists as null → violates
// NOT NULL constraint). Sending explicit zeros satisfies the DB.
const templateBaseFields = {
  templateColor: "0",
  recursiveIndexDepth: "1",
  collectionPosition: "0",
  templatePosition: "0",
} as const;

// POST /templates/update with no templateId → creates a new template.
// Returns the full toArray() response body (includes widgetArray).
async function createTemplate(
  page: Page,
  name = "Test Template",
): Promise<TemplateShape> {
  const res = await page.request.post(`${baseURL()}/templates/update`, {
    headers: { Accept: "application/json" },
    form: { name, includeInSearch: "On", ...templateBaseFields },
  });
  expect(res.status()).toBe(200);
  return res.json() as Promise<TemplateShape>;
}

// Minimal form fields for a single new widget (fieldTitle empty → server-generated).
function newWidgetFields(
  index: number,
  label: string,
  overrides: Record<string, string> = {},
): Record<string, string> {
  return {
    [`widget[${index}][label]`]: label,
    [`widget[${index}][fieldTitle]`]: "",
    [`widget[${index}][fieldType]`]: "1", // field type 1 = "text" (always in seed data)
    [`widget[${index}][viewOrder]`]: String(index + 1),
    [`widget[${index}][templateOrder]`]: String(index + 1),
    [`widget[${index}][fieldData]`]: "",
    [`widget[${index}][clickToSearchType]`]: "0",
    [`widget[${index}][tooltip]`]: "",
    ...overrides,
  };
}

// Shared matchers ─────────────────────────────────────────────────────────────

const templateShape = {
  id: expect.any(Number),
  name: expect.any(String),
  createdAt: expect.any(String),
  modifiedAt: expect.any(String),
  showCollection: expect.any(Boolean),
  showTemplate: expect.any(Boolean),
  includeInSearch: expect.any(Boolean),
  indexForSearching: expect.any(Boolean),
  isHidden: expect.any(Boolean),
  templateColor: expect.any(Number),
  recursiveIndexDepth: expect.any(Number),
  widgetArray: expect.any(Array),
};

const widgetShape = {
  widgetId: expect.any(Number),
  fieldTitle: expect.any(String),
  label: expect.any(String),
  fieldType: expect.any(String),
  fieldTypeId: expect.any(Number),
  clickToSearchType: expect.any(Number),
};

// ─── Baseline CRUD tests (develop API surface) ───────────────────────────────

test.describe("templates", () => {
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

  // --- auth ---

  test("unauthenticated JSON request returns 401", async ({ browser }) => {
    const ctx = await browser.newContext();
    const response = await ctx.request.get(`${baseURL()}/templates/`, {
      headers: { Accept: "application/json" },
    });
    expect(response.status()).toBe(401);
    await ctx.close();
  });

  // --- list ---

  test("returns array of templates", async ({ page }) => {
    const response = await page.request.get(`${baseURL()}/templates/`, {
      headers: { Accept: "application/json" },
    });
    expect(response.ok()).toBe(true);
    const body = (await response.json()) as unknown[];
    expect(Array.isArray(body)).toBe(true);
  });

  // --- create ---

  test("creates a template and returns summary", async ({ page }) => {
    const template = await createBasicTemplate(page, {
      name: "My New Template",
    });
    expect(template.id).toBeGreaterThan(0);
    expect(template.name).toBe("My New Template");
    expect(template.createdAt).toBeTruthy();
    expect(template.modifiedAt).toBeTruthy();
  });

  test("created template appears in list", async ({ page }) => {
    const template = await createBasicTemplate(page, {
      name: "Listed Template",
    });

    const listResponse = await page.request.get(`${baseURL()}/templates/`, {
      headers: { Accept: "application/json" },
    });
    const list = (await listResponse.json()) as Array<{
      id: number;
      name: string;
    }>;
    expect(list.some((t) => t.id === template.id)).toBe(true);
  });

  // --- update ---

  test("updates an existing template's name", async ({ page }) => {
    const created = await createBasicTemplate(page, { name: "Original Name" });

    const updateResponse = await page.request.post(
      `${baseURL()}/templates/update`,
      {
        headers: { Accept: "application/json" },
        form: {
          templateId: String(created.id),
          name: "Updated Name",
          templateColor: "1",
          recursiveIndexDepth: "1",
          collectionPosition: "0",
          templatePosition: "0",
        },
      },
    );

    expect(updateResponse.ok()).toBe(true);
    const updated = (await updateResponse.json()) as {
      id: number;
      name: string;
    };
    expect(updated.id).toBe(created.id);
    expect(updated.name).toBe("Updated Name");
  });

  // --- delete ---

  test("deletes a template successfully", async ({ page }) => {
    const template = await createBasicTemplate(page, { name: "To Be Deleted" });

    const deleteResponse = await page.request.get(
      `${baseURL()}/templates/delete/${template.id}`,
      { headers: { Accept: "application/json" } },
    );
    expect(deleteResponse.ok()).toBe(true);
    const body = (await deleteResponse.json()) as { success: boolean };
    expect(body.success).toBe(true);
  });

  test("deleted template no longer appears in list", async ({ page }) => {
    const template = await createBasicTemplate(page, { name: "Gone Template" });

    await page.request.get(`${baseURL()}/templates/delete/${template.id}`, {
      headers: { Accept: "application/json" },
    });

    const listResponse = await page.request.get(`${baseURL()}/templates/`, {
      headers: { Accept: "application/json" },
    });
    const list = (await listResponse.json()) as Array<{ id: number }>;
    expect(list.some((t) => t.id === template.id)).toBe(false);
  });
});

// ─── Richer API tests (feat-update-template-api-for-editor) ──────────────────
//
// Tests for the getTemplate() endpoint and widgetArray / fieldTitle behaviour
// introduced by this branch for the Vue template editor.

test.describe("templates API", () => {
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

  // ── GET /templates/getTemplate/{id} ─────────────────────────────────────────

  test.describe("GET getTemplate", () => {
    // G1: happy path — returns full template shape
    test("returns full template shape for a valid template", async ({
      page,
    }) => {
      const created = await createTemplate(page, "Shape Test Template");

      const res = await page.request.get(
        `${baseURL()}/templates/getTemplate/${created.id}`,
        { headers: { Accept: "application/json" } },
      );

      expect(res.status()).toBe(200);
      const body = (await res.json()) as TemplateShape;
      expect(body).toMatchObject(templateShape);
      expect(body.id).toBe(created.id);
      expect(body.name).toBe("Shape Test Template");
      expect(Array.isArray(body.widgetArray)).toBe(true);
    });

    // G2: unauthenticated — returns 401
    test("returns 401 for unauthenticated requests", async ({ request }) => {
      // `request` fixture is a fresh context with no session cookies.
      const res = await request.get(`${baseURL()}/templates/getTemplate/1`, {
        headers: { Accept: "application/json" },
      });
      expect(res.status()).toBe(401);
    });

    // G3: non-existent ID — returns 404
    test("returns 404 for a non-existent template ID", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/templates/getTemplate/999999`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(404);
    });
  });

  // ── POST /templates/update ───────────────────────────────────────────────────

  test.describe("POST update", () => {
    // U1: create new template (no templateId) — returns toArray() shape with empty widgetArray
    test("creates a new template and returns toArray shape", async ({
      page,
    }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: { name: "Brand New Template", ...templateBaseFields },
      });

      expect(res.status()).toBe(200);
      const body = (await res.json()) as TemplateShape;
      expect(body).toMatchObject({
        ...templateShape,
        name: "Brand New Template",
      });
      expect(typeof body.id).toBe("number");
      expect(body.widgetArray).toHaveLength(0);
    });

    // U2: update existing template — name is reflected in response
    test("updates an existing template and returns updated data", async ({
      page,
    }) => {
      const created = await createTemplate(page, "Original Name");

      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: {
          templateId: String(created.id),
          name: "Updated Name",
          ...templateBaseFields,
        },
      });

      expect(res.status()).toBe(200);
      const body = (await res.json()) as TemplateShape;
      expect(body.id).toBe(created.id);
      expect(body.name).toBe("Updated Name");
    });

    // U3: new widget with empty fieldTitle gets a server-generated fieldTitle
    test("generates fieldTitle server-side for new widgets", async ({
      page,
    }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: {
          name: "Widget FieldTitle Test",
          ...templateBaseFields,
          ...newWidgetFields(0, "Title"),
        },
      });

      expect(res.status()).toBe(200);
      const body = (await res.json()) as TemplateShape;
      expect(body.widgetArray).toHaveLength(1);
      // Pattern: <lowercased_label>_<instanceId>  (instance 1 in seed data)
      expect(body.widgetArray[0].fieldTitle).toBe("title_1");
    });

    // U4: existing widget fieldTitle is round-tripped unchanged
    test("preserves an existing widget fieldTitle on re-save", async ({
      page,
    }) => {
      // Create a template with a widget (gets a generated fieldTitle).
      const first = await (async () => {
        const res = await page.request.post(`${baseURL()}/templates/update`, {
          headers: { Accept: "application/json" },
          form: {
            name: "Preserve FieldTitle",
            ...templateBaseFields,
            ...newWidgetFields(0, "Title"),
          },
        });
        return res.json() as Promise<TemplateShape>;
      })();

      const lockedFieldTitle = first.widgetArray[0].fieldTitle;

      // Re-save the same template sending the locked fieldTitle back.
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: {
          templateId: String(first.id),
          name: "Preserve FieldTitle",
          ...templateBaseFields,
          ...newWidgetFields(0, "Title", {
            "widget[0][fieldTitle]": lockedFieldTitle,
          }),
        },
      });

      expect(res.status()).toBe(200);
      const body = (await res.json()) as TemplateShape;
      expect(body.widgetArray[0].fieldTitle).toBe(lockedFieldTitle);
    });

    // U5: two new widgets with the same label get distinct, deduplicated fieldTitles
    test("deduplicates fieldTitles for two new widgets with the same label", async ({
      page,
    }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: {
          name: "Dedup Test",
          ...templateBaseFields,
          ...newWidgetFields(0, "Title"),
          ...newWidgetFields(1, "Title"),
        },
      });

      expect(res.status()).toBe(200);
      const body = (await res.json()) as TemplateShape;
      expect(body.widgetArray).toHaveLength(2);
      const [first, second] = body.widgetArray;
      expect(first.fieldTitle).toBe("title_1");
      expect(second.fieldTitle).toBe("title_1_2");
    });

    // U6: label made entirely of non-alphanumeric characters falls back to field_<instanceId>
    test("falls back to field_<instanceId> for non-alphanumeric label", async ({
      page,
    }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: {
          name: "Fallback FieldTitle Test",
          ...templateBaseFields,
          ...newWidgetFields(0, "!!!"),
        },
      });

      expect(res.status()).toBe(200);
      const body = (await res.json()) as TemplateShape;
      expect(body.widgetArray).toHaveLength(1);
      expect(body.widgetArray[0].fieldTitle).toBe("field_1");
    });

    // U7: fieldTypeId is present, numeric, and matches the fieldType name lookup
    test("includes both fieldType name and fieldTypeId for round-trip safety", async ({
      page,
    }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: {
          name: "FieldTypeId Test",
          ...templateBaseFields,
          ...newWidgetFields(0, "Description"),
        },
      });

      expect(res.status()).toBe(200);
      const body = (await res.json()) as TemplateShape;
      const widget = body.widgetArray[0];
      expect(widget).toMatchObject(widgetShape);
      expect(widget.fieldType).toBe("text"); // field_type id=1 name="text"
      expect(widget.fieldTypeId).toBe(1);
    });

    // U8: clickToSearchType defaults to 0 when not supplied
    test("clickToSearchType defaults to 0 when omitted", async ({ page }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: {
          name: "clickToSearchType Default Test",
          ...templateBaseFields,
          // Intentionally omit clickToSearchType
          "widget[0][label]": "Some Field",
          "widget[0][fieldTitle]": "",
          "widget[0][fieldType]": "1",
          "widget[0][viewOrder]": "1",
          "widget[0][templateOrder]": "1",
          "widget[0][fieldData]": "",
          "widget[0][tooltip]": "",
        },
      });

      expect(res.status()).toBe(200);
      const body = (await res.json()) as TemplateShape;
      expect(body.widgetArray[0].clickToSearchType).toBe(0);
    });

    // U9: widget with a blank label is skipped and not saved
    test("skips widgets with blank labels", async ({ page }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: {
          name: "Skip Blank Label Test",
          ...templateBaseFields,
          ...newWidgetFields(0, "Real Field"),
          ...newWidgetFields(1, ""), // blank label — should be skipped
          ...newWidgetFields(2, "  "), // whitespace-only label — also skipped
        },
      });

      expect(res.status()).toBe(200);
      const body = (await res.json()) as TemplateShape;
      expect(body.widgetArray).toHaveLength(1);
      expect(body.widgetArray[0].label).toBe("Real Field");
    });
  });
});
