<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

class TestHelper extends Instance_Controller
{

  // Allow unauthenticated access — the env var is the gate.
  public $noAuth = true;

  public function __construct()
  {
    parent::__construct();
  }

  // POST /{instance}/testhelper/resetDb
  // Truncates user-writable tables and resets sequences back to seed state.
  // Returns JSON { status: 'ok' } on success.
  // Returns 403 if ELEVATOR_TEST_RESET_ENABLED is not 'true'.
  public function resetDb(): void
  {
    if (getenv('ELEVATOR_TEST_RESET_ENABLED') !== 'true') {
      render_json(['status' => 'error', 'message' => 'not enabled'], 403);
      return;
    }

    if ($this->input->method() !== 'post') {
      render_json(['status' => 'error', 'message' => 'method not allowed'], 405);
      return;
    }

    $conn = $this->doctrine->em->getConnection();

    // Truncate in dependency order. CASCADE handles FK children automatically.
    // Extend this list as new test types are added (e.g. assets, drawers).
    $conn->executeStatement('TRUNCATE TABLE collections CASCADE');
    $conn->executeStatement("SELECT setval('collections_id_seq', 1, false)");

    render_json(['status' => 'ok']);
  }
}
