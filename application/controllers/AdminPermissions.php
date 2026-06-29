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
      "helpText" => "Everyone, including signed-out visitors.",
      // vestigial group_value
      "ignoresGroupValues" => true,
    ],
    AUTHED_TYPE => [
      "name" => AUTHED_TYPE,
      "label" => "Authenticated Users",
      "helpText" => "Anyone signed in, by any login method.",
      "ignoresGroupValues" => true,
    ],
    REMOTE_TYPE => [
      "name" => REMOTE_TYPE,
      "label" => "Centrally Authenticated Users",
      "helpText" => "Users signed in through central "
        . "single sign-on (SSO).",
      "ignoresGroupValues" => true,
    ],
    USER_TYPE => [
      "name" => USER_TYPE,
      "label" => "Specific People",
      "helpText" => "Specific people you choose. Add by name, email, or username.",
    ],
  ];

  private AuthHelper $authHelper;

  public function __construct() {
    parent::__construct();
    $this->load->library('SimpleValidator');
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

    // reshape each match for the new UI: a plain list, with the id named
    // localUserId instead of the legacy completionId
    $matches = array_map(
      fn($match) => [
        "name" => $match["name"],
        "email" => $match["email"],
        "localUserId" => $match["completionId"],
        "username" => $match["username"],
      ],
      array_values($this->authHelper->autocompleteUsername($query))
    );

    return render_json(['matches' => $matches]);
  }

  /**
   * REST entry point for /adminPermissions/groups[/{id}].
   *
   * Trailing URL segments arrive as method args, so a request to
   * /adminPermissions/groups/5 calls this with $groupId = "5".
   */
  public function groups($groupId = null, $subResource = null, $memberId = null) {
    $this->abortUnlessAdmin();

    $method = $this->input->server('REQUEST_METHOD');

    $groupId = $groupId === null
      ? null
      : filter_var($groupId, FILTER_VALIDATE_INT);
    $memberId = $memberId === null
      ? null
      : filter_var($memberId, FILTER_VALIDATE_INT);

    // a non-numeric id segment becomes false
    if ($groupId === false || $memberId === false) {
      return abort_json(['error' => 'Invalid ID'], 400);
    }

    // which resource does the URL address?
    $route = match (true) {
      $groupId === null => '/groups',
      $subResource === null => '/groups/{id}',
      $subResource !== 'members' => 'unknown',
      $memberId === null => '/groups/{id}/members',
      default => '/groups/{id}/members/{userId}',
    };

    switch ($route) {
      case '/groups':
        switch ($method) {
          case 'GET':
            return $this->listGroups();
          case 'POST':
            return $this->createGroup();
        }
        break;
      case '/groups/{id}':
        switch ($method) {
          case 'GET':
            return $this->showGroup($groupId);
          case 'PUT':
          case 'PATCH':
            return $this->updateGroup($groupId);
          case 'DELETE':
            return $this->deleteGroup($groupId);
        }
        break;
      case '/groups/{id}/members':
        switch ($method) {
          case 'GET':
            return $this->listGroupMembers($groupId);
          case 'POST':
            return $this->addGroupMember($groupId);
        }
        break;
      case '/groups/{id}/members/{userId}':
        switch ($method) {
          case 'DELETE':
            return $this->removeGroupMember($groupId, $memberId);
        }
        break;
      case 'unknown':
        return abort_json(['error' => 'Not Found'], 404);
    }

    // a known route, but the verb isn't allowed on it
    return abort_json(['error' => 'Method Not Allowed'], 405);
  }

  private function listGroups() {
    $groups = $this->doctrine->em
      ->getRepository(InstanceGroup::class)
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
   */
  private function deleteGroup(int $groupId) {
    $group = $this->doctrine->em
      ->getRepository(InstanceGroup::class)
      ->findOneBy(['id' => $groupId, 'instance' => $this->instance]);
    if (!$group) {
      return abort_json(['error' => 'Group not found'], 404);
    }

    $this->doctrine->em->remove($group);
    $this->doctrine->em->flush();

    $this->clearUserCache();

    return render_json(['deleted' => $groupId]);
  }

  /**
   * GET /adminPermissions/groups/{id}/members — the group's members,
   * resolved to names so the UI can show who belongs.
   */
  private function listGroupMembers(int $groupId) {
    $group = $this->doctrine->em
      ->getRepository(InstanceGroup::class)
      ->findOneBy(['id' => $groupId, 'instance' => $this->instance]);
    if (!$group) {
      return abort_json(['error' => 'Group not found'], 404);
    }

    return render_json(['members' => $this->resolveMembers($group)]);
  }

  /**
   * POST /adminPermissions/groups/{id}/members — add one member.
   *
   * Exactly one of two fields per request: `localUserId` for someone who
   * already has a local row, or `remoteUserId` (a netid/username) for someone
   * not local yet, who we provision on the spot via firstOrCreateLocalUser.
   */
  private function addGroupMember(int $groupId) {
    $group = $this->doctrine->em
      ->getRepository(InstanceGroup::class)
      ->findOneBy(['id' => $groupId, 'instance' => $this->instance]);
    if (!$group) {
      return abort_json(['error' => 'Group not found'], 404);
    }

    if ($group->getGroupType() !== USER_TYPE) {
      return abort_json(
        ['error' => 'Only Specific People groups take members'],
        422
      );
    }

    $body = $this->requestBody();
    $hasLocalId = isset($body['localUserId']) && $body['localUserId'] !== '';
    $hasRemoteId = isset($body['remoteUserId'])
      && trim((string) $body['remoteUserId']) !== '';

    // the field name carries the intent, so require exactly one
    if ($hasLocalId === $hasRemoteId) {
      return abort_json(
        ['error' => 'Provide exactly one of localUserId or remoteUserId'],
        422
      );
    }

    if ($hasLocalId) {
      try {
        $validated = V::validate($body, [
          'localUserId' => [V::required(), V::integer()],
        ]);
      } catch (ValidationException $e) {
        return abort_json(['errors' => $e->getErrors()], 422);
      }
      $user = $this->doctrine->em
        ->getRepository("Entity\User")
        ->find((int) $validated['localUserId']);
      if (!$user) {
        return abort_json(['error' => 'User not found'], 422);
      }
    } else {
      // not local yet: provision a Remote user from the typed username.
      // trim to match the presence check above, so surrounding whitespace
      // doesn't turn a valid username into a lookup miss
      $remoteUserId = trim((string) $body['remoteUserId']);
      try {
        $user = $this->firstOrProvisionRemoteUser($remoteUserId);
      } catch (RemoteUserNotFoundException $e) {
        return abort_json(['error' => $e->getMessage()], 404);
      }
    }

    $userId = $user->getId();

    foreach ($group->getGroupValues() as $entry) {
      if ((int) $entry->getGroupValue() === $userId) {
        return abort_json(['error' => 'User is already a member'], 409);
      }
    }

    $entry = new Entity\GroupEntry();
    $entry->setGroupValue($userId);
    $group->addGroupValue($entry);

    $this->doctrine->em->flush();
    $this->clearUserCache();

    return render_json(['member' => $this->memberPayload($user)], 201);
  }

  /**
   * DELETE /adminPermissions/groups/{id}/members/{userId} — drop a member.
   */
  private function removeGroupMember(int $groupId, int $userId) {
    $group = $this->doctrine->em
      ->getRepository(InstanceGroup::class)
      ->findOneBy(['id' => $groupId, 'instance' => $this->instance]);
    if (!$group) {
      return abort_json(['error' => 'Group not found'], 404);
    }

    if ($group->getGroupType() !== USER_TYPE) {
      return abort_json(
        ['error' => 'Only Specific People groups take members'],
        422
      );
    }

    $entry = null;
    foreach ($group->getGroupValues() as $candidate) {
      if ((int) $candidate->getGroupValue() === $userId) {
        $entry = $candidate;
        break;
      }
    }
    if (!$entry) {
      return abort_json(['error' => 'User is not a member'], 404);
    }

    $group->removeGroupValue($entry); // orphanRemoval deletes on flush
    $this->doctrine->em->flush();
    $this->clearUserCache();

    return render_json(['removed' => $userId]);
  }

  /**
   * Resolve a group's entries to member display data. Only User groups hold
   * user ids; other types store raw attribute strings and have no members.
   */
  private function resolveMembers(InstanceGroup $group): array {
    if ($group->getGroupType() !== USER_TYPE) {
      return [];
    }

    $userIds = array_map(
      fn($entry) => (int) $entry->getGroupValue(),
      $group->getGroupValues()->toArray()
    );
    if (count($userIds) === 0) {
      return [];
    }

    // one query for every member, not one query per member
    $users = $this->doctrine->em
      ->getRepository("Entity\User")
      ->findBy(['id' => $userIds], ['displayName' => 'ASC']);

    return array_map(fn($user) => $this->memberPayload($user), $users);
  }

  private function memberPayload(Entity\User $user): array {
    return [
      'userId' => $user->getId(),
      'name' => $user->getDisplayName(),
      'email' => $user->getEmail(),
      'username' => $user->getUsername(),
      'userType' => $user->getUserType(),
      'createdAt' => $user->getCreatedAt()?->format('c'),
    ];
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
          // a non-numeric value is a remote username; provision its
          // local row and store the resulting user id
          try {
            $value = $this->firstOrProvisionRemoteUser($value)->getId();
          } catch (RemoteUserNotFoundException $e) {
            return abort_json(['error' => $e->getMessage()], 404);
          }
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

      // a value-based group with no members is meaningless; require at
      // least one so we don't silently create an empty group
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
    return self::GLOBAL_GROUP_TYPES[$type]['ignoresGroupValues'] ?? false;
  }

  /**
   * Find a remote user within the local DB by their remote id
   * (e.g. username, umndid). If not found, creates a new
   * user in the local DB with the remoteUserId set.
   *
   * @throws RemoteUserNotFoundException if the user cannot be found or
   *   provisioned.
   * @return Entity\User the user record matching the remote id
   */
  private function firstOrProvisionRemoteUser(string $remoteUserId): Entity\User {

    /** @var AuthHelper $authHelper */
    $authHelper = $this->user_model->getAuthHelper();

    // findById($id, true) will make new (unsaved) an Entity\User record with
    // the given remote id if nothing is found.
    /** @var ?Entity\User $remoteUser */
    $remoteUser = $authHelper->findById($remoteUserId, true)[0] ?? null;

    if ($remoteUser === null) {
      throw new RemoteUserNotFoundException($remoteUserId);
    }

    // does a user already exist in the local DB with this username?
    /** @var ?Entity\User $existingUser */
    $existingUser = $this->doctrine->em->getRepository(Entity\User::class)
      ->findOneBy(["username" => $remoteUser->getUsername()]);

    // if so, return it instead of the new unsaved one
    if ($existingUser !== null) {
      return $existingUser;
    }

    // otherwise, fill in some blanks in the new record
    $remoteUser->setUserType("Remote");
    $remoteUser->setCreatedAt(new \DateTime("now"));
    $remoteUser->setInstance($this->instance);

    // and then save it
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
