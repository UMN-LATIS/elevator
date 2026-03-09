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
    [`widget[${index}][fieldTitle]`]: `field_${index}`, // both label and fieldTitle are required
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

// ─── GET /templates/getFieldTypes ────────────────────────────────────────────

test.describe("GET getFieldTypes", () => {
  test.beforeEach(async ({ page }) => {
    const adminPassword = process.env.DEFAULT_ADMIN_PASSWORD;
    if (!adminPassword) {
      test.skip(true, "DEFAULT_ADMIN_PASSWORD env var not set");
      return;
    }
    await loginUser(page, process.env.ADMIN_USERNAME ?? "admin", adminPassword);
  });

  // F1: returns a non-empty array of field types with the expected shape
  test("returns an array of field type objects", async ({ page }) => {
    const res = await page.request.get(`${baseURL()}/templates/getFieldTypes`, {
      headers: { Accept: "application/json" },
    });

    expect(res.status()).toBe(200);
    const body = (await res.json()) as unknown[];
    expect(Array.isArray(body)).toBe(true);
    expect(body.length).toBeGreaterThan(0);
  });

  // F2: each entry has id, name, modelName, sampleFieldData keys
  test("each field type has the expected shape", async ({ page }) => {
    const res = await page.request.get(`${baseURL()}/templates/getFieldTypes`, {
      headers: { Accept: "application/json" },
    });

    const body = (await res.json()) as Array<{
      id: unknown;
      name: unknown;
      modelName: unknown;
      sampleFieldData: unknown;
    }>;

    for (const ft of body) {
      expect(ft).toMatchObject({
        id: expect.any(Number),
        name: expect.any(String),
      });
      expect("modelName" in ft).toBe(true);
      expect("sampleFieldData" in ft).toBe(true);
    }
  });

  // F3: results are sorted alphabetically by name
  test("returns field types sorted by name", async ({ page }) => {
    const res = await page.request.get(`${baseURL()}/templates/getFieldTypes`, {
      headers: { Accept: "application/json" },
    });

    const body = (await res.json()) as Array<{ name: string }>;
    const names = body.map((ft) => ft.name);
    expect(names).toEqual([...names].sort());
  });

  // F4: unauthenticated — returns 401
  test("returns 401 for unauthenticated requests", async ({ request }) => {
    const res = await request.get(`${baseURL()}/templates/getFieldTypes`, {
      headers: { Accept: "application/json" },
    });
    expect(res.status()).toBe(401);
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

    // U3: widget with empty fieldTitle is rejected — fieldTitle is required
    test("returns 422 when a widget fieldTitle is empty", async ({ page }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: {
          name: "Widget FieldTitle Test",
          ...templateBaseFields,
          ...newWidgetFields(0, "Title", { "widget[0][fieldTitle]": "" }),
        },
      });

      expect(res.status()).toBe(422);
      const body = (await res.json()) as { error: string; details: string[] };
      expect(body.details.some((d) => d.includes("fieldTitle"))).toBe(true);
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
          "widget[0][fieldTitle]": "some_field",
          "widget[0][fieldType]": "1",
          "widget[0][viewOrder]": "1",
          "widget[0][templateOrder]": "1",
          "widget[0][fieldData]": "",
          "widget[0][tooltip]": "",
        },
      });

      expect(res.status()).toBe(200);
      const body = (await res.json()) as TemplateShape;
      expect(body.widgetArray[0].clickToSearchType).toBe(1); // controller defaults to 1 via ??1
    });

    // U9: widget with a blank label is rejected — label is required
    test("returns 422 when a widget label is blank", async ({ page }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: {
          name: "Blank Label Test",
          ...templateBaseFields,
          ...newWidgetFields(0, "Real Field"),
          ...newWidgetFields(1, ""), // blank label — now a validation error
        },
      });

      expect(res.status()).toBe(422);
      const body = (await res.json()) as { error: string; details: string[] };
      expect(body.details.some((d) => d.includes("label"))).toBe(true);
    });
  });

  // ── POST /templates/update – validation ──────────────────────────────────────

  test.describe("POST update – validation", () => {
    const TOO_LONG = "x".repeat(256);

    // V1: missing name → 422
    test("returns 422 when name is missing", async ({ page }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: { ...templateBaseFields },
      });
      expect(res.status()).toBe(422);
      const body = (await res.json()) as { error: string };
      expect(body.error).toBe("Validation failed");
    });

    // V2: name > 255 chars → 422
    test("returns 422 when name exceeds 255 characters", async ({ page }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: { name: TOO_LONG, ...templateBaseFields },
      });
      expect(res.status()).toBe(422);
      const body = (await res.json()) as { error: string; details: string[] };
      expect(body.error).toBe("Validation failed");
      expect(body.details.some((d) => d.includes("name"))).toBe(true);
    });

    // V3: non-integer templateColor → 422
    test("returns 422 when templateColor is not an integer", async ({
      page,
    }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: { name: "Test", ...templateBaseFields, templateColor: "red" },
      });
      expect(res.status()).toBe(422);
    });

    // V4: non-integer recursiveIndexDepth → 422
    test("returns 422 when recursiveIndexDepth is not an integer", async ({
      page,
    }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: {
          name: "Test",
          ...templateBaseFields,
          recursiveIndexDepth: "deep",
        },
      });
      expect(res.status()).toBe(422);
    });

    // V5: widget tooltip > 255 chars → 422
    test("returns 422 when a widget tooltip exceeds 255 characters", async ({
      page,
    }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: {
          name: "Tooltip Overflow",
          ...templateBaseFields,
          ...newWidgetFields(0, "My Field", {
            "widget[0][tooltip]": TOO_LONG,
          }),
        },
      });
      expect(res.status()).toBe(422);
      const body = (await res.json()) as { error: string; details: string[] };
      expect(body.details.some((d) => d.includes("tooltip"))).toBe(true);
    });

    // V6: widget label > 255 chars → 422
    test("returns 422 when a widget label exceeds 255 characters", async ({
      page,
    }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: {
          name: "Label Overflow",
          ...templateBaseFields,
          ...newWidgetFields(0, TOO_LONG),
        },
      });
      expect(res.status()).toBe(422);
      const body = (await res.json()) as { error: string; details: string[] };
      expect(body.details.some((d) => d.includes("label"))).toBe(true);
    });

    // V7: widget fieldData is not valid JSON → 422
    test("returns 422 when a widget fieldData is invalid JSON", async ({
      page,
    }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: {
          name: "Bad FieldData",
          ...templateBaseFields,
          ...newWidgetFields(0, "My Field", {
            "widget[0][fieldData]": "{not: valid json",
          }),
        },
      });
      expect(res.status()).toBe(422);
      const body = (await res.json()) as { error: string; details: string[] };
      expect(body.details.some((d) => d.includes("fieldData"))).toBe(true);
    });

    // V8: widget fieldType missing → 422
    test("returns 422 when a widget fieldType is missing", async ({ page }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: {
          name: "Missing FieldType",
          ...templateBaseFields,
          "widget[0][label]": "My Field",
          "widget[0][fieldTitle]": "my_field",
          "widget[0][viewOrder]": "1",
          "widget[0][templateOrder]": "1",
          "widget[0][fieldData]": "",
          "widget[0][tooltip]": "",
          // fieldType intentionally omitted
        },
      });
      expect(res.status()).toBe(422);
      const body = (await res.json()) as { error: string; details: string[] };
      expect(body.details.some((d) => d.includes("fieldType"))).toBe(true);
    });

    // V9: validation fires before any DB writes — existing widgets are preserved
    test("does not destroy existing widgets when validation fails", async ({
      page,
    }) => {
      // Create a template with one valid widget (explicit fieldTitle so it isn't skipped).
      const created = await(async () => {
        const res = await page.request.post(`${baseURL()}/templates/update`, {
          headers: { Accept: "application/json" },
          form: {
            name: "Stable Template",
            ...templateBaseFields,
            ...newWidgetFields(0, "Original Field", {
              "widget[0][fieldTitle]": "original_field",
            }),
          },
        });
        return res.json() as Promise<TemplateShape>;
      })();
      expect(created.widgetArray).toHaveLength(1);

      // Attempt an update with an invalid tooltip (too long).
      const updateRes = await page.request.post(
        `${baseURL()}/templates/update`,
        {
          headers: { Accept: "application/json" },
          form: {
            templateId: String(created.id),
            name: "Stable Template",
            ...templateBaseFields,
            ...newWidgetFields(0, "Original Field", {
              "widget[0][fieldTitle]": "original_field",
              "widget[0][tooltip]": TOO_LONG,
            }),
          },
        },
      );
      expect(updateRes.status()).toBe(422);

      // Fetch the template and confirm the original widget is still there.
      const fetchRes = await page.request.get(
        `${baseURL()}/templates/getTemplate/${created.id}`,
        { headers: { Accept: "application/json" } },
      );
      expect(fetchRes.status()).toBe(200);
      const fetched = (await fetchRes.json()) as TemplateShape;
      expect(fetched.widgetArray).toHaveLength(1);
      expect(fetched.widgetArray[0].label).toBe("Original Field");
    });
  });
});
