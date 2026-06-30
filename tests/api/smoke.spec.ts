import { test, expect } from "@playwright/test";
import { loginUser, baseURL } from "../helpers";

test.describe("smoke", () => {
  test("default instance is reachable", async ({ page }) => {
    const response = await page.goto(baseURL());
    expect(response?.status()).toBe(200);
  });

  test("login returns success and sets session cookie", async ({
    page,
    context,
  }) => {
    await loginUser(page, "admin");

    const cookies = await context.cookies();
    const sessionCookie = cookies.find((c) => c.name === "ci_session");
    expect(sessionCookie).toBeDefined();
  });
});
