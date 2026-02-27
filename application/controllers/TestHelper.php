<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

// Test-only endpoint for resetting database state between Playwright test runs.
//
// This controller is intentionally unauthenticated. The security gate is CodeIgniter's
// ENVIRONMENT constant, which is set at boot from $_SERVER['CI_ENV'] in index.php.
// The endpoint is only active when ENVIRONMENT is 'local' or 'testing' — never in production.
//
// Locally, CI_ENV defaults to 'local' in .env.example — no change needed.
// In CI, set CI_ENV=testing. Production servers must not have CI_ENV set to 'local' or 'testing'.
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
      $conn->executeStatement('TRUNCATE TABLE widgets CASCADE');
      $conn->executeStatement("SELECT setval('widgets_id_seq', 1, false)");
      $conn->executeStatement('TRUNCATE TABLE templates CASCADE');
      $conn->executeStatement("SELECT setval('templates_id_seq', 1, false)");
      $conn->executeStatement('TRUNCATE TABLE collections CASCADE');
      $conn->executeStatement("SELECT setval('collections_id_seq', 1, false)");
    } catch (\Throwable $e) {
      log_message('error', 'TestHelper::resetDb failed: ' . $e->getMessage());
      render_json(['status' => 'error', 'message' => 'internal error'], 500);
      return;
    }

    render_json(['status' => 'ok']);
  }
}
