import { test, expect, type Page } from "@playwright/test";
import { baseURL } from "../helpers";

// MockAuthHelper (AUTH_HELPER=MockAuthHelper) fakes the remote-auth surface
// with canned data: /loginManager/remoteLogin signs in a superadmin Remote
// user with no credentials, populateUserData returns fixed hints, and a
// canned directory backs findById/autocomplete. These tests cover the flows
// no local login can reach (populated entryHints, remote member-add) and
// skip when the instance runs a different helper, e.g. a UMN-configured
// dev machine.

type EntryHint = { value: string; label: string };

type GroupType = {
  type: string;
  label: string;
  description: string;
  entryHints: EntryHint[];
};

// Sign in through the mock remote flow. Returns false when the instance is
// not mock-configured: a real helper redirects to Shibboleth, nothing
// authenticates, and the admin probe comes back 401.
async function loginMockRemote(page: Page): Promise<boolean> {
  // A real helper 302s toward its identity provider here. Swallow any
  // network error from an unreachable IdP so the probe below can skip
  // instead of failing.
  await page.request
    .get(`${baseURL()}/loginManager/remoteLogin`)
    .catch(() => undefined);
  const probe = await page.request.get(
    `${baseURL()}/adminPermissions/groupTypes`,
    { headers: { Accept: "application/json" } },
  );
  return probe.status() === 200;
}

async function fetchGroupTypes(page: Page): Promise<GroupType[]> {
  const res = await page.request.get(
    `${baseURL()}/adminPermissions/groupTypes`,
    { headers: { Accept: "application/json" } },
  );
  expect(res.status()).toBe(200);
  const { groupTypes } = (await res.json()) as { groupTypes: GroupType[] };
  return groupTypes;
}

test.describe("mock remote auth", () => {
  test.beforeEach(async ({ page }) => {
    const isMockConfigured = await loginMockRemote(page);
    test.skip(!isMockConfigured, "instance is not running MockAuthHelper");
  });

  // Provisioned user rows (mockinstructor, mockstudent) persist across
  // runs, the flows under test tolerate existing rows. Groups created
  // here are deleted in the test that makes them.

  test("remoteLogin signs in with no credentials and exposes the mock types", async ({
    page,
  }) => {
    // beforeEach already logged in; a 200 admin read proves the session.
    const groupTypes = await fetchGroupTypes(page);
    const types = groupTypes.map((groupType) => groupType.type);
    expect(types).toEqual(
      expect.arrayContaining([
        "Unit",
        "JobCode",
        "Class Number",
        "Dept/Course Number",
        "StudentStatus",
        "EmployeeType",
      ]),
    );
  });

  test("entryHints are populated for the mock remote user", async ({
    page,
  }) => {
    const groupTypes = await fetchGroupTypes(page);
    const byType = new Map(groupTypes.map((g) => [g.type, g]));

    // Class Number hints are keyed by numeric class numbers in the mock
    // (as at UMN), so string values here pin the int-coercion cast in
    // entryHintsForType end to end.
    const classNumber = byType.get("Class Number");
    expect(classNumber?.entryHints).toContainEqual({
      value: "12345",
      label: "ART.1234.001",
    });

    // JobCode hints are keyed by string codes in the mock.
    expect(byType.get("JobCode")?.entryHints).toContainEqual({
      value: "9403",
      label: "Instructor",
    });

    // Global types stay empty even when the session has userData.
    expect(byType.get("User")?.entryHints).toEqual([]);
  });

  test("adds a group member by remote id from the mock directory", async ({
    page,
  }) => {
    const createRes = await page.request.post(
      `${baseURL()}/adminPermissions/groups`,
      { form: { type: "User", label: "Mock directory members" } },
    );
    expect(createRes.status()).toBe(201);
    const { group } = (await createRes.json()) as { group: { id: number } };

    // mockstudent has no local row yet, so this exercises provisioning
    // through MockAuthHelper::findById.
    const addRes = await page.request.post(
      `${baseURL()}/adminPermissions/groups/${group.id}/members`,
      { form: { remoteUserId: "mockstudent" } },
    );
    expect(addRes.status()).toBe(201);
    const { member } = (await addRes.json()) as {
      member: { username: string; name: string };
    };
    expect(member.username).toBe("mockstudent");
    expect(member.name).toBe("Mock Student");

    const listRes = await page.request.get(
      `${baseURL()}/adminPermissions/groups/${group.id}/members`,
      { headers: { Accept: "application/json" } },
    );
    expect(listRes.status()).toBe(200);
    const { members } = (await listRes.json()) as {
      members: { username: string }[];
    };
    expect(members.map((m) => m.username)).toContain("mockstudent");

    // The reset script doesn't cover groups, so clean up our own.
    const deleteRes = await page.request.delete(
      `${baseURL()}/adminPermissions/groups/${group.id}`,
    );
    expect(deleteRes.status()).toBe(200);
  });
});
