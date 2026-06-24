<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

use Entity\InstanceGroup;
use Entity\Permission;
use SimpleValidator as V;

/**
 * pure json api for new ui
 */
class AdminPermissions extends Instance_Controller {
  // mirrors the structure of AuthHelpers::$authTypes,
  // @see UMNHelper:$authTypes for an example
  const GLOBAL_GROUP_TYPES = [
    ALL_TYPE => [
      "name" => ALL_TYPE,
      "label" => "All",
      "helpText" => "Matches everyone, including signed-out visitors.",
      // vestigial group_value
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

    $groupTypes = array_map(
      fn($t) => [
        "type" => $t["name"],
        "label" => $t["label"],
        "description" => $t["helpText"] ?? "",
      ],
      array_values($this->getGroupTypes())
    );

    return render_json(["groupTypes" => $groupTypes]);
  }

  public function permissionLevels() {
    $this->abortUnlessAdmin();

    $permissions = $this->doctrine->em
      ->getRepository(Permission::class)
      ->findAll();

    // level is a string column; sort numerically, ascending
    usort($permissions, fn($a, $b) => (int) $a->getLevel() <=> (int) $b->getLevel());

    return render_json([
      "permissionLevels" => $permissions,
    ]);
  }

  /**
   * GET /adminPermissions/userAutocomplete?q=... suggests people for a
   * "Specific People" group's member field. Read only.
   *
   * Every school searches local users. Schools with an external directory
   * (UMN, St. Olaf) also return matches from central auth.
   */
  public function userAutocomplete() {
    $this->abortUnlessAdmin();

    if ($this->input->server('REQUEST_METHOD') !== 'GET') {
      return abort_json(['error' => 'Method Not Allowed'], 405);
    }

    $query = trim((string) $this->input->get('q'));

    // ignore trivial input to avoid expensive autocompleteUsername calls
    if (mb_strlen($query) < 2) {
      return render_json(['matches' => []]);
    }

    // autocompleteUsername returns an associative array, so convert to a plain array
    $matches = array_values($this->authHelper->autocompleteUsername($query));

    return render_json(['matches' => $matches]);
  }

  /**
   * REST entry point for /adminPermissions/groups[/{id}].
   *
   * Trailing URL segments arrive as method args, so a request to
   * /adminPermissions/groups/5 calls this with $groupId = "5".
   */
  public function groups($groupId = null) {
    $this->abortUnlessAdmin();

    // use HTTP verb to determine action
    $method = $this->input->server('REQUEST_METHOD');


    // /adminPermissions/groups
    if ($groupId === null) {
      switch ($method) {
        case 'GET':
          return $this->listGroups();
        case 'POST':
          return $this->createGroup();
        default:
          return abort_json(['error' => 'Method Not Allowed'], 405);
      }
    }

    // /adminPermissions/groups/{id}
    $groupId = filter_var($groupId, FILTER_VALIDATE_INT);
    if ($groupId === false) {
      return abort_json(['error' => 'Invalid group ID'], 400);
    }

    switch ($method) {
      case 'GET':
        return $this->showGroup($groupId);
      case 'PUT':
      case 'PATCH':
        return $this->updateGroup($groupId);
      case 'DELETE':
        return $this->deleteGroup($groupId);
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

  /**
   * GET /adminPermissions/groups/{id} — the full group record.
   */
  private function showGroup(int $groupId) {
    $group = $this->doctrine->em
      ->getRepository(InstanceGroup::class)
      ->findOneBy(['id' => $groupId, 'instance' => $this->instance]);

    if (!$group) {
      abort_json(['error' => 'Group not found'], 404);
    }

    return render_json(['group' => $group]);
  }

  /**
   * PUT|PATCH /adminPermissions/groups/{id} — edit a group's label and type.
   *
   * Changing the type clears existing members, since they belong to the old
   * type. Editing only the label leaves them alone.
   */
  private function updateGroup(int $groupId) {
    $group = $this->doctrine->em
      ->getRepository(InstanceGroup::class)
      ->findOneBy(['id' => $groupId, 'instance' => $this->instance]);
    if (!$group) {
      return abort_json(['error' => 'Group not found'], 404);
    }

    try {
      $validated = V::validate(
        $this->requestBody(),
        $this->groupAttributeRules()
      );
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    $newType = $validated['type'];
    $hasTypeChanged = $newType !== $group->getGroupType();

    $group->setGroupLabel($validated['label']);

    if ($hasTypeChanged) {
      $group->setGroupType($newType);

      // a type change invalidates old members, so clear them. toArray() copies
      // first so removing entries does not mutate the list mid-loop.
      foreach ($group->getGroupValues()->toArray() as $entry) {
        $group->removeGroupValue($entry); // orphanRemoval deletes on flush
      }
      $group->setGroupValue($this->ignoresGroupValues($newType) ? 1 : null);
    }

    $this->doctrine->em->flush();

    $this->clearUserCache();

    return render_json(['group' => $group]);
  }

  /**
   * DELETE /adminPermissions/groups/{id} — remove a group.
   *
   * TODO: remove the group (and its GroupEntry rows) and clearUserCache().
   */
  private function deleteGroup(int $groupId) {
    return abort_json(['error' => 'Not Implemented'], 501);
  }

  private function getGroupTypes(): array {
    return [
      ...self::GLOBAL_GROUP_TYPES,
      ...$this->authHelper->authTypes,
    ];
  }

  /**
   * Shared validation rules for a group's editable attributes (label +
   * type). createGroup adds its own `values` rule on top of these.
   */
  private function groupAttributeRules(): array {
    $validTypes = array_keys($this->getGroupTypes());
    return [
      'type' => [
        V::required(),
        fn($v) => !isset($v) || in_array($v, $validTypes, true)
          ? true
          : 'Unknown group type',
      ],
      'label' => [
        V::required(),
        V::maxLength(255),
        // reject `< > "` to prevent HTML injection, while still allowing
        // names like `R&D` and `Bob's Team`.
        fn($v) => !isset($v) || !preg_match('/[<>"]/', $v)
          ? true
          : 'Label cannot contain < > or " characters',
      ],
    ];
  }

  /**
   * Validate the posted payload and persist a new InstanceGroup.
   */
  private function createGroup() {
    try {
      $validated = V::validate($this->requestBody(), [
        ...$this->groupAttributeRules(),
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

      return true;
    };
  }

  /**
   * Whether `$type` matches a whole population instead of a values
   * list (All/Authed/Authed_remote).
   */
  private function ignoresGroupValues(string $type): bool {
    // auth-helper types always carry values, so absence means false
    return self::GLOBAL_GROUP_TYPES[$type]['ignoresGroupValues'] ?? false;
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
