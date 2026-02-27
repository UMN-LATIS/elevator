import { defineConfig, devices } from "@playwright/test";

export default defineConfig({
  testDir: "./tests/api",
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  // API tests share a single database — serialise to prevent concurrent
  // refreshDatabase() calls from wiping data that a parallel test depends on.
  workers: 1,
  reporter: [
    ["list"],
    ["html", { open: "never" }],
    ["json", { outputFile: "test-results/results.json" }],
    ["junit", { outputFile: "test-results/results.xml" }],
  ],
  use: {
    baseURL: process.env.BASE_URL ?? "http://localhost/defaultinstance",
    ignoreHTTPSErrors: true,
  },
  projects: [
    {
      name: "chromium",
      use: { ...devices["Desktop Chrome"] },
    },
  ],
});
