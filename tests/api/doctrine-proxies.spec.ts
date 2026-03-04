import { test, expect } from "@playwright/test";
import { execSync } from "child_process";
import path from "path";
import { loginUser, baseURL } from "../helpers";

// Doctrine generates a PHP subclass ("proxy") for each entity in
// application/models/Proxies/. These files enable lazy-loading: when you
// access a relationship that hasn't been fetched yet (e.g. $template->getWidgets()),
// the proxy fires the SQL query transparently. Without the proxy file on disk,
// Doctrine throws a fatal error.
//
// In dev (caching off) we use AUTOGENERATE_FILE_NOT_EXISTS: proxies are
// written on first request and reused thereafter. These tests verify that
// the app serves entity-touching endpoints correctly even when the Proxies/
// directory starts empty — i.e. that on-demand generation actually works.

const projectRoot = path.resolve(__dirname, "../..");
const proxiesDir = "/var/www/html/application/models/Proxies";

function clearProxies(): void {
  execSync(
    `docker compose exec -T php-fpm sh -c "rm -f ${proxiesDir}/*.php"`,
    { cwd: projectRoot, stdio: "pipe" },
  );
}

function countProxyFiles(): number {
  const result = execSync(
    `docker compose exec -T php-fpm sh -c "ls ${proxiesDir}/*.php 2>/dev/null | wc -l"`,
    { cwd: projectRoot, stdio: "pipe" },
  );
  return parseInt(result.toString().trim(), 10);
}

test.describe("doctrine proxy generation", () => {
  test.beforeEach(async ({ page }) => {
    const adminPassword = process.env.DEFAULT_ADMIN_PASSWORD;
    if (!adminPassword) {
      test.skip(true, "DEFAULT_ADMIN_PASSWORD env var not set");
      return;
    }
    await loginUser(page, process.env.ADMIN_USERNAME ?? "admin", adminPassword);
  });

  test("app serves entity endpoints with an empty Proxies directory", async ({
    page,
  }) => {
    clearProxies();
    expect(countProxyFiles()).toBe(0);

    // Templates loads Entity\Template and touches its OneToMany Widget
    // relationship — this requires the proxy class to exist or be generated.
    const response = await page.request.get(`${baseURL()}/templates/`, {
      headers: { Accept: "application/json" },
    });

    expect(response.ok()).toBe(true);
  });

  test("proxy files are created on first request", async ({ page }) => {
    clearProxies();

    await page.request.get(`${baseURL()}/templates/`, {
      headers: { Accept: "application/json" },
    });

    // At least one proxy class should have been written to disk.
    expect(countProxyFiles()).toBeGreaterThan(0);
  });

  test("subsequent requests succeed without regenerating proxies", async ({
    page,
  }) => {
    // Warm the proxy dir with a first request.
    await page.request.get(`${baseURL()}/templates/`, {
      headers: { Accept: "application/json" },
    });

    const beforeCount = countProxyFiles();
    expect(beforeCount).toBeGreaterThan(0);

    // Second request — FILE_NOT_EXISTS means no writes should occur.
    await page.request.get(`${baseURL()}/templates/`, {
      headers: { Accept: "application/json" },
    });

    expect(countProxyFiles()).toBe(beforeCount);
  });
});
