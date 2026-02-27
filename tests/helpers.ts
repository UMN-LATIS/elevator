import { type Page } from "@playwright/test";

export interface WidgetForm {
  label: string;
  /** field_type id from seed data (1=text, 2=upload, 3=date, 4=select, etc.); defaults to 1 */
  fieldType?: number;
  /** Omit to trigger server-side fieldTitle generation */
  fieldTitle?: string;
  tooltip?: string;
  templateOrder?: number;
  viewOrder?: number;
}

export interface CreateTemplateOptions {
  name?: string;
  widgets?: WidgetForm[];
}

export interface TemplateResponse {
  id: number;
  name: string;
  createdAt: string;
  modifiedAt: string;
  showCollection: boolean;
  showCollectionPosition: number;
  showTemplate: boolean;
  showTemplatePosition: number;
  includeInSearch: boolean;
  indexForSearching: boolean;
  isHidden: boolean;
  templateColor: number;
  recursiveIndexDepth: number;
  widgetArray: WidgetResponse[];
}

export interface WidgetResponse {
  widgetId: number;
  fieldTitle: string;
  label: string;
  tooltip: string | null;
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
}

export const baseURL = (): string =>
  process.env.BASE_URL ?? "http://localhost/defaultinstance";

// POST /{instance}/loginmanager/localLoginAsync
// Fields: username, password (application/x-www-form-urlencoded)
// Sets ci_session cookie on the page context on success.
export async function loginUser(
  page: Page,
  username: string,
  password: string,
): Promise<void> {
  const response = await page.request.post(
    `${baseURL()}/loginmanager/localLoginAsync`,
    {
      form: { username, password },
    },
  );

  if (!response.ok()) {
    const body = (await response.json().catch(() => ({}))) as {
      message?: string;
    };
    throw new Error(
      `Login failed (${response.status()}): ${body.message ?? "unknown error"}`,
    );
  }

  // ci_session cookie is set automatically on the page.request context.
  // Subsequent page.goto() calls in the same context will include it.
}

// POST /{instance}/testhelper/resetDb
// Truncates user-writable tables back to seed state.
// Requires CI_ENV=local or CI_ENV=testing on the server (returns 403 in production).
export async function refreshDatabase(page: Page): Promise<void> {
  const response = await page.request.post(`${baseURL()}/testhelper/resetDb`);

  if (!response.ok()) {
    const body = (await response.json().catch(() => ({}))) as {
      message?: string;
    };
    throw new Error(
      `DB reset failed (${response.status()}): ${
        body.message ?? "unknown error"
      }`,
    );
  }
}

// POST /{instance}/templates/update (no templateId = create)
// Sends form-encoded data matching the legacy template form.
// Returns the full Template::toArray() shape (JSON response).
export async function createTemplate(
  page: Page,
  options: CreateTemplateOptions = {},
): Promise<TemplateResponse> {
  const name = options.name ?? "Test Template";
  const widgets = options.widgets ?? [];

  const formData: Record<string, string> = {
    name,
    templateColor: "1",
    recursiveIndexDepth: "1",
    collectionPosition: "0",
    templatePosition: "0",
  };

  widgets.forEach((w, i) => {
    formData[`widget[${i}][label]`] = w.label;
    formData[`widget[${i}][fieldType]`] = String(w.fieldType ?? 1);
    formData[`widget[${i}][templateOrder]`] = String(w.templateOrder ?? i + 1);
    formData[`widget[${i}][viewOrder]`] = String(w.viewOrder ?? i + 1);
    formData[`widget[${i}][tooltip]`] = w.tooltip ?? "";
    formData[`widget[${i}][fieldData]`] = "";
    if (w.fieldTitle !== undefined) {
      formData[`widget[${i}][fieldTitle]`] = w.fieldTitle;
    }
  });

  const response = await page.request.post(`${baseURL()}/templates/update`, {
    headers: { Accept: "application/json" },
    form: formData,
  });

  if (!response.ok()) {
    const body = (await response.json().catch(() => ({}))) as {
      error?: string;
    };
    throw new Error(
      `createTemplate failed (${response.status()}): ${
        body.error ?? "unknown"
      }`,
    );
  }

  return response.json() as Promise<TemplateResponse>;
}
