<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

use SimpleValidator as V;

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
      "helpText" => "Matches everyone, including signed-out visitors.",
      // matches a whole population: no group_values list, just the
      // vestigial scalar group_value
      "ignoresGroupValues" => true,
    ],
    AUTHED_TYPE => [
      "name" => AUTHED_TYPE,
      "label" => "Authenticated Users",
      "ignoresGroupValues" => true,
    ],
    REMOTE_TYPE => [
      "name" => REMOTE_TYPE,
      "label" => "Centrally Authenticated Users",
      "ignoresGroupValues" => true,
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
      "groupTypes" => $this->getValidGroupTypes(),
    ]);
  }

  public function permissionLevels() {
    $this->abortUnlessAdmin();

    $permissions = $this->doctrine->em
      ->getRepository("Entity\Permission")
      ->findAll();

    // level is a string column; sort numerically, ascending
    usort($permissions, fn($a, $b) => (int) $a->getLevel() <=> (int) $b->getLevel());

    return render_json([
      "permissionLevels" => $permissions,
    ]);
  }

  /**
   * REST entry point for /adminPermissions/groups.
   */
  public function groups() {
    $this->abortUnlessAdmin();

    // the custom router has no route table, so dispatch on verb here
    switch ($this->input->server('REQUEST_METHOD')) {
      case 'GET':
        return $this->listGroups();
      case 'POST':
        return $this->createGroup();
      default:
        return abort_json(['error' => 'Method Not Allowed'], 405);
    }
  }

  private function listGroups() {
    $groups = $this->doctrine->em
      ->getRepository("Entity\InstanceGroup")
      ->findBy(['instance' => $this->instance]);

    return render_json([
      "groups" => $groups,
    ]);
  }

  private function getValidGroupTypes(): array {
    return array_keys([
      ...self::GLOBAL_GROUPS,
      ...$this->authHelper->authTypes,
    ]);
  }

  /**
   * Validate the posted payload and persist a new InstanceGroup.
   */
  private function createGroup() {
    $post = $this->input->post() ?? [];

    $validTypes = $this->getValidGroupTypes();

    try {
      $validated = V::validate($post, [
        'type' => [
          V::required(),
          fn($v) => !isset($v) || in_array($v, $validTypes, true)
            ? true
            : 'Unknown group type',
        ],
        'label' => [V::required(), V::maxLength(255)],
        'values' => [V::array(), $this->groupValuesValidator()],
      ]);
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    $type = $validated['type'];

    $group = new Entity\InstanceGroup();
    $group->setInstance($this->instance);
    $group->setGroupType($type);
    $group->setGroupLabel($validated['label']);

    if ($this->ignoresGroupValues($type)) {
      // vestigial scalar; must be 1 — Authed/Authed_remote match on it
      $group->setGroupValue(1);
    } else {
      $group->setGroupValue(null);

      $nonEmptyValues = array_filter(
        (array) ($validated['values'] ?? []),
        fn($v) => $v !== ''
      );

      // create a GroupEntry for each value
      foreach ($nonEmptyValues as $value) {
        // a non-numeric User value is a remote auth-system id; resolve
        // it to a local user id. other types store their value as-is
        if ($type === USER_TYPE && !is_numeric($value)) {
          $user = $this->firstOrCreateLocalUser($value);
          if (!$user) {
            return abort_json(
              ['error' => "Could not find a user matching '{$value}'"],
              422
            );
          }
          $value = $user->getId();
        }

        $entry = new Entity\GroupEntry();
        $entry->setGroupValue($value);
        $group->addGroupValue($entry);
      }
    }

    $this->doctrine->em->persist($group);
    $this->doctrine->em->flush();

    $this->clearUserCache();

    return render_json(['group' => $group], 201);
  }

  /**
   * Validator for the `values` field, keyed off the group `type`.
   */
  private function groupValuesValidator(): \Closure {
    return function ($v, $data) {
      $entries = array_filter((array) $v, fn($x) => $x !== '');

      // reject stray values so an "All" group can't pose as a
      // "specific people" one
      if ($this->ignoresGroupValues($data['type'] ?? '')) {
        return count($entries) === 0
          ? true
          : 'This group type does not accept values';
      }

      return count($entries) > 0
        ? true
        : 'This group type requires at least one value';
    };
  }

  /**
   * Whether `$type` matches a whole population instead of a values
   * list (All/Authed/Authed_remote).
   */
  private function ignoresGroupValues(string $type): bool {
    // auth-helper types always carry values, so absence means false
    return self::GLOBAL_GROUPS[$type]['ignoresGroupValues'] ?? false;
  }

  /**
   * First-or-create the local User for a remote auth-system id (e.g. a
   * umndid, not a username). Null if the auth helper doesn't know them.
   */
  private function firstOrCreateLocalUser(string $remoteUserId): ?Entity\User {
    $matches = $this->authHelper->findById($remoteUserId, true);
    if (count($matches) === 0) {
      return null;
    }
    $remoteUser = $matches[0];

    $existing = $this->doctrine->em
      ->getRepository("Entity\User")
      ->findOneBy(['username' => $remoteUser->getUsername()]);
    if ($existing) {
      return $existing;
    }

    // first time we've seen them — persist as a Remote user
    $remoteUser->setUserType('Remote');
    $remoteUser->setCreatedAt(new \DateTime('now'));
    $remoteUser->setInstance($this->instance);
    $this->doctrine->em->persist($remoteUser);
    $this->doctrine->em->flush();

    return $remoteUser;
  }

  /**
   * Clear cached user permissions after a group mutation so the change
   * takes effect immediately.
   */
  private function clearUserCache(): void {
    if ($this->config->item('enableCaching')) {
      $this->userCache->clear();
    }
  }
}
