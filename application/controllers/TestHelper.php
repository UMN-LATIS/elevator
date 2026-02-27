<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

// Test-only endpoint for resetting database state between Playwright test runs.
//
// This controller is intentionally unauthenticated. The security gate is CodeIgniter's
// ENVIRONMENT constant, which is set at boot from the CI_ENV env var (index.php).
// The endpoint is only active when CI_ENV is 'local' or 'testing' — never in production.
//
// Why ENVIRONMENT and not a custom flag? This mirrors Laravel's APP_ENV=testing pattern:
// the framework's own environment concept is the gate, not a separate opt-in variable.
// Locally, set CI_ENV=local (default in .env.example) or CI_ENV=testing.
// In CI, set CI_ENV=testing. Production servers must have CI_ENV=production.
class TestHelper extends Instance_Controller
{
  // POST /{instance}/testhelper/resetDb
  // Truncates user-writable tables and resets sequences back to seed state.
  // Returns JSON { status: 'ok' } on success, 403 outside local/testing environments.
  public function resetDb(): void
  {
    if (!in_array(ENVIRONMENT, ['local', 'testing'])) {
      render_json(['status' => 'error', 'message' => 'not enabled'], 403);
      return;
    }

    if ($this->input->method() !== 'post') {
      render_json(['status' => 'error', 'message' => 'method not allowed'], 405);
      return;
    }

    $conn = $this->doctrine->em->getConnection();

    try {
      // Truncate in dependency order. CASCADE handles FK children automatically.
      // Extend this list as new test types are added (e.g. assets, drawers).
      $conn->executeStatement('TRUNCATE TABLE collections CASCADE');
      $conn->executeStatement("SELECT setval('collections_id_seq', 1, false)");
    } catch (\Throwable $e) {
      render_json(['status' => 'error', 'message' => $e->getMessage()], 500);
      return;
    }

    render_json(['status' => 'ok']);
  }
}
