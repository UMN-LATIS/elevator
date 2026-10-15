import { test, expect } from "@playwright/test";
import { loginUser, baseURL, queryDb } from "../helpers";

const INSTANCE_ID = 1;

function setEnableThemesInDb(value: "NULL" | "false"): void {
  queryDb(`UPDATE instances SET enablethemes = ${value} WHERE id = ${INSTANCE_ID}`);
}

function enableThemesInDb(): string {
  return queryDb(`SELECT enablethemes FROM instances WHERE id = ${INSTANCE_ID}`);
}

function toFormDataLikeUpdateInstanceSettings(
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
  test.afterEach(() => {
    setEnableThemesInDb("false");
  });

  test("saves unchanged settings when enableThemes is NULL in the database", async ({
    page,
  }) => {
    setEnableThemesInDb("NULL");
    await loginUser(page, "admin");

    const settingsResponse = await page.request.get(
      `${baseURL()}/instances/getInstance/${INSTANCE_ID}`,
    );
    expect(settingsResponse.ok()).toBe(true);
    const settings = (await settingsResponse.json()) as Record<string, unknown>;
    expect(settings.enableTheming).toBeNull();

    const saveResponse = await page.request.post(
      `${baseURL()}/instances/save/true`,
      {
        // Not multipart: Playwright drops "" fields from
        // multipart bodies, so enableTheming="" never reaches
        // Instances::save and ELEVATOR-2FG never fires.
        form: toFormDataLikeUpdateInstanceSettings({
          ...settings,
          instanceId: INSTANCE_ID,
        }),
      },
    );

    expect(saveResponse.status()).toBe(200);
    expect(enableThemesInDb()).toBe("f");
  });
});
