import { test, expect, type Page } from "@playwright/test";
import {
  loginUser,
  refreshDatabase,
  createTemplate as createBasicTemplate,
  baseURL,
} from "../helpers";

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


test.describe("templates", () => {
  test.beforeAll(() => {
    refreshDatabase();
  });

  test.beforeEach(async ({ page }) => {
    await loginUser(page, "admin");
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

test.describe("GET getFieldTypes", () => {
  test.beforeEach(async ({ page }) => {
    await loginUser(page, "admin");
  });

  test("returns an array of field type objects", async ({ page }) => {
    const res = await page.request.get(`${baseURL()}/templates/getFieldTypes`, {
      headers: { Accept: "application/json" },
    });

    expect(res.status()).toBe(200);
    const body = (await res.json()) as unknown[];
    expect(Array.isArray(body)).toBe(true);
    expect(body.length).toBeGreaterThan(0);
  });

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

  test("returns field types sorted by name", async ({ page }) => {
    const res = await page.request.get(`${baseURL()}/templates/getFieldTypes`, {
      headers: { Accept: "application/json" },
    });

    const body = (await res.json()) as Array<{ name: string }>;
    const names = body.map((ft) => ft.name);
    expect(names).toEqual([...names].sort());
  });

  test("returns 401 for unauthenticated requests", async ({ request }) => {
    const res = await request.get(`${baseURL()}/templates/getFieldTypes`, {
      headers: { Accept: "application/json" },
    });
    expect(res.status()).toBe(401);
  });

  // Field types the backend flags as carrying JSON config (getHasFieldData()).
  const FIELD_DATA_TYPE_NAMES = [
    "upload",
    "select",
    "multiselect",
    "related asset",
  ];

  interface FieldTypeEntry {
    name: string;
    hasFieldData: unknown;
    sampleFieldData: unknown;
  }

  async function fetchFieldTypesByName(
    page: Page,
  ): Promise<Map<string, FieldTypeEntry>> {
    const res = await page.request.get(`${baseURL()}/templates/getFieldTypes`, {
      headers: { Accept: "application/json" },
    });
    expect(res.status()).toBe(200);
    const body = (await res.json()) as FieldTypeEntry[];
    return new Map(body.map((ft) => [ft.name, ft]));
  }

  test("sampleFieldData is a string or null for every field type", async ({
    page,
  }) => {
    const byName = await fetchFieldTypesByName(page);
    for (const ft of byName.values()) {
      const isStringOrNull =
        typeof ft.sampleFieldData === "string" || ft.sampleFieldData === null;
      expect(isStringOrNull, `${ft.name} sampleFieldData`).toBe(true);
    }
  });

  test("hasFieldData is a boolean flagged for config-carrying types", async ({
    page,
  }) => {
    const byName = await fetchFieldTypesByName(page);
    for (const ft of byName.values()) {
      expect(typeof ft.hasFieldData, `${ft.name} hasFieldData`).toBe("boolean");
    }
    for (const name of FIELD_DATA_TYPE_NAMES) {
      expect(byName.get(name)?.hasFieldData, name).toBe(true);
    }
    expect(byName.get("text")?.hasFieldData, "text").toBe(false);
  });

  test("unescapes a valid-JSON sample into parseable text", async ({ page }) => {
    const upload = await fetchFieldTypesByName(page).then((m) => m.get("upload"));
    const sample = upload?.sampleFieldData;
    expect(typeof sample).toBe("string");
    const sampleText = sample as string;
    expect(sampleText.includes("\\n"), "no literal backslash-n").toBe(false);
    expect(sampleText.includes('\\"'), "no literal backslash-quote").toBe(false);
    const parsed = JSON.parse(sampleText) as Record<string, unknown>;
    expect(parsed.enableTiling).toBe(true);
  });

  test("serves a non-JSON sample without breaking", async ({ page }) => {
    const select = await fetchFieldTypesByName(page).then((m) => m.get("select"));
    expect(typeof select?.sampleFieldData).toBe("string");
    expect(() => JSON.parse(select?.sampleFieldData as string)).toThrow();
  });
});

test.describe("templates API", () => {
  test.beforeEach(async ({ page }) => {
    await loginUser(page, "admin");
  });

  test.afterEach(() => {
    refreshDatabase();
  });

  test.describe("GET getTemplate", () => {
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

    test("returns 401 for unauthenticated requests", async ({ request }) => {
      // `request` fixture is a fresh context with no session cookies.
      const res = await request.get(`${baseURL()}/templates/getTemplate/1`, {
        headers: { Accept: "application/json" },
      });
      expect(res.status()).toBe(401);
    });

    test("returns 404 for a non-existent template ID", async ({ page }) => {
      const res = await page.request.get(
        `${baseURL()}/templates/getTemplate/999999`,
        { headers: { Accept: "application/json" } },
      );
      expect(res.status()).toBe(404);
    });
  });

  test.describe("POST update", () => {
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

    test.skip("deduplicates fieldTitles for two new widgets with the same label", async ({
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

    test.skip("falls back to field_<instanceId> for non-alphanumeric label", async ({
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

    test("clickToSearchType defaults to 1 when omitted", async ({ page }) => {
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

  test.describe("POST update – validation", () => {
    const TOO_LONG = "x".repeat(256);

    test("returns 422 when name is missing", async ({ page }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: { ...templateBaseFields },
      });
      expect(res.status()).toBe(422);
      const body = (await res.json()) as { error: string };
      expect(body.error).toBe("Validation failed");
    });

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

    test("returns 422 when templateColor is not an integer", async ({
      page,
    }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: { name: "Test", ...templateBaseFields, templateColor: "red" },
      });
      expect(res.status()).toBe(422);
    });

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

    test("returns 422 when a widget tooltip exceeds 2000 characters", async ({
      page,
    }) => {
      const res = await page.request.post(`${baseURL()}/templates/update`, {
        headers: { Accept: "application/json" },
        form: {
          name: "Tooltip Overflow",
          ...templateBaseFields,
          ...newWidgetFields(0, "My Field", {
            "widget[0][tooltip]": "x".repeat(2001),
          }),
        },
      });
      expect(res.status()).toBe(422);
      const body = (await res.json()) as { error: string; details: string[] };
      expect(body.details.some((d) => d.includes("tooltip"))).toBe(true);
    });

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
              "widget[0][tooltip]": "x".repeat(2001),
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
