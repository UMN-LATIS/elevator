<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

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

  private AuthHelper $authHelper;

  public function __construct() {
    parent::__construct();
    $this->authHelper = $this->user_model->getAuthHelper();
  }


  public function groupTypes() {
    $this->abortUnlessAdmin();

    return render_json([
      "groupTypes" => array_values([
        ...self::GLOBAL_GROUPS,
        ...$this->authHelper->authTypes
      ]),
    ]);
  }

  public function permissionLevels() {
    $this->abortUnlessAdmin();

    $permissions = $this->doctrine->em
      ->getRepository("Entity\Permission")
      ->findAll();

    // level is a string column, so cast to int
    // and then sort by level ascending
    usort($permissions, fn($a, $b) => (int) $a->getLevel() <=> (int) $b->getLevel());

    return render_json([
      "permissionLevels" => $permissions,
    ]);
  }
}
