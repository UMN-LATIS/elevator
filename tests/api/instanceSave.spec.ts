import { test, expect } from "@playwright/test";
import { loginUser, baseURL, queryDb } from "../helpers";

const INSTANCE_ID = 1;
const SNAPSHOT_TABLE = "instance_save_spec_snapshot";

const NULLABLE_COLUMNS = [
  { column: "enablethemes", field: "enableTheming", savedAs: "false" },
  { column: "usecustomheader", field: "useCustomHeader", savedAs: "NULL" },
  { column: "maximummorelikethis", field: "maximumMoreLikeThis", savedAs: "NULL" },
  {
    column: "defaulttexttruncationheight",
    field: "defaultTextTruncationHeight",
    savedAs: "NULL",
  },
  { column: "availablethemes", field: "availableThemes", savedAs: "NULL" },
];

function snapshotInstanceRow(): void {
  queryDb(
    `DROP TABLE IF EXISTS ${SNAPSHOT_TABLE}; CREATE TABLE ${SNAPSHOT_TABLE} AS SELECT * FROM instances WHERE id = ${INSTANCE_ID}`,
  );
}

function restoreInstanceRow(): void {
  const columns = queryDb(
    `SELECT string_agg(column_name, ', ') FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'instances' AND column_name <> 'id'`,
  );
  queryDb(
    `UPDATE instances SET (${columns}) = (SELECT ${columns} FROM ${SNAPSHOT_TABLE}) WHERE id = ${INSTANCE_ID}; DROP TABLE ${SNAPSHOT_TABLE}`,
  );
}

function setInstanceColumnToNull(column: string): void {
  queryDb(`UPDATE instances SET ${column} = NULL WHERE id = ${INSTANCE_ID}`);
}

function instanceColumnInDb(column: string): string {
  return queryDb(
    `SELECT coalesce(${column}::text, 'NULL') FROM instances WHERE id = ${INSTANCE_ID}`,
  );
}

function toUpdateInstanceSettingsFormData(
  settings: Record<string, unknown>,
): FormData {
  const formData = new FormData();
  Object.entries(settings).forEach(([key, value]) => {
    if (value === null || value === undefined) {
      formData.append(key, "");
    } else if (Array.isArray(value)) {
      value.forEach((item) => formData.append(`${key}[]`, String(item)));
    } else if (typeof value === "boolean") {
      formData.append(key, value ? "1" : "0");
    } else {
      formData.append(key, String(value));
    }
  });
  return formData;
}

test.describe("Instances::save from the instance settings page", () => {
  test.beforeEach(() => {
    snapshotInstanceRow();
  });

  test.afterEach(() => {
    restoreInstanceRow();
  });

  for (const { column, field, savedAs } of NULLABLE_COLUMNS) {
    test(`saves unchanged settings when ${column} is NULL`, async ({ page }) => {
      setInstanceColumnToNull(column);
      await loginUser(page, "admin");

      const settingsResponse = await page.request.get(
        `${baseURL()}/instances/getInstance/${INSTANCE_ID}`,
      );
      expect(settingsResponse.ok()).toBe(true);
      const settings = (await settingsResponse.json()) as Record<string, unknown>;
      expect(settings[field]).toBeNull();

      const saveResponse = await page.request.post(
        `${baseURL()}/instances/save/true`,
        {
          // Not multipart: Playwright drops "" fields from
          // multipart bodies, so Instances::save would
          // never receive the "" that the settings page
          // sends for null.
          form: toUpdateInstanceSettingsFormData({
            ...settings,
            instanceId: INSTANCE_ID,
          }),
        },
      );

      expect(saveResponse.status()).toBe(200);
      expect(instanceColumnInDb(column)).toBe(savedAs);
    });
  }
});
