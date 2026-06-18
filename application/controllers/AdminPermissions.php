<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

use App\Enums\GroupType;

/**
 * pure json api for new ui
 */
class AdminPermissions extends Instance_Controller {

  public function __construct() {
    parent::__construct();

    $this->load->library('SimpleValidator');
  }

  public function groupTypes() {
    $this->abortUnlessAdmin();

    $groupTypes = array_map(fn($type) => $type->toArray(), GroupType::cases());

    return render_json(["groupTypes" => $groupTypes]);
  }
}
