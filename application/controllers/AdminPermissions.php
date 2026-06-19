<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

use App\Enums\GroupType;

/**
 * pure json api for new ui
 */
class AdminPermissions extends Instance_Controller {
  // mirrors the structure of AuthHelpers::$authTypes,
  // @see UMNHelper:$authTypes for an example
  const GLOBAL_GROUPS = [
    ALL_TYPE => [
      "name" => ALL_TYPE,
      "label" => "All",
      "helpText" => "Matches everyone, including signed-out visitors."
    ],
     AUTHED_TYPE => [
      "name" => AUTHED_TYPE,
      "label" => "Authenticated Users"
    ],
     REMOTE_TYPE => [
      "name" => REMOTE_TYPE,
      "label" => "Centrally Authenticated Users"
    ],
     USER_TYPE => [
      "name" => USER_TYPE,
      "label" => "Specific People"
    ],
   ];


  public function groupTypes() {
    $this->abortUnlessAdmin();

    $groupTypes = array_map(fn($type) => $type->toArray(), GroupType::cases());

    return render_json(["groupTypes" => $groupTypes]);
  }
}
