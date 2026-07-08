<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

use Doctrine\ORM\EntityManager;
use Entity\InstanceGroup;
use Entity\Permission;
use Entity\CollectionPermission;
use Entity\InstancePermission;
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
  private EntityManager $em;

  public function __construct() {
    parent::__construct();
    $this->load->library('SimpleValidator');
    $this->authHelper = $this->user_model->getAuthHelper();
    $this->em = $this->doctrine->em;
  }


  public function groupTypes() {
    $this->abortUnlessAdmin();

    $groupTypes = array_map(
      fn($t) => [
        "type" => $t["name"],
        "label" => $t["label"],
        "description" => $t["helpText"] ?? "",
        "entryHints" => $this->entryHintsForType($t["name"]),
      ],
      array_values($this->getGroupTypes())
    );

    return render_json(["groupTypes" => $groupTypes]);
  }

  /**
   * Suggested entry values for one group type, from the signed-in
   * admin's session userData.
   *
   * Auth helpers key hints by raw value with a human label, and PHP
   * coerces numeric keys to ints, so each pair is recast to a
   * {value, label} string object for the UI combobox. Local admins
   * and helperless instances have no userData, so an empty list is
   * normal, not an error.
   */
  private function entryHintsForType(string $type): array {
    // Global types never take entries. Guard by type category rather
    // than trusting that no auth helper ever keys its userData by a
    // global type name, since helpers pick their keys independently.
    if (!$this->isAuthHelperGroupType($type)) {
      return [];
    }

    $hints = $this->user_model->userData[$type]["hints"] ?? [];

    $entryHints = [];
    foreach ($hints as $rawValue => $label) {
      $entryHints[] = [
        "value" => (string) $rawValue,
        "label" => (string) $label,
      ];
    }

    return $entryHints;
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
  public function groups($groupId = null, $subresource = null, $subresourceId = null) {
    $this->abortUnlessAdmin();

    $method = $this->input->server('REQUEST_METHOD');

    $groupId = $groupId === null
      ? null
      : filter_var($groupId, FILTER_VALIDATE_INT);
    $subresourceId = $subresourceId === null
      ? null
      : filter_var($subresourceId, FILTER_VALIDATE_INT);

    // a non-numeric id segment becomes false
    if ($groupId === false || $subresourceId === false) {
      return abort_json(['error' => 'Invalid ID'], 400);
    }

    // Build the route pattern straight from the URL shape, so the $table
    // keys below are the single list of known routes.
    $route = '/groups';
    if ($groupId !== null) {
      $route .= '/{id}';
    }
    if ($subresource !== null) {
      $route .= '/' . $subresource;
    }
    if ($subresourceId !== null) {
      $route .= '/{subresourceId}';
    }

    $table = [
      '/groups' => [
        'GET' => fn() => $this->listGroups(),
        'POST' => fn() => $this->createGroup(),
      ],
      '/groups/{id}' => [
        'GET' => fn() => $this->showGroup($groupId),
        'PUT' => fn() => $this->updateGroup($groupId),
        'PATCH' => fn() => $this->updateGroup($groupId),
        'DELETE' => fn() => $this->deleteGroup($groupId),
      ],
      '/groups/{id}/members' => [
        'GET' => fn() => $this->listGroupMembers($groupId),
        'POST' => fn() => $this->addGroupMember($groupId),
      ],
      '/groups/{id}/members/{subresourceId}' => [
        'DELETE' => fn() => $this->removeGroupMember($groupId, $subresourceId),
      ],
      '/groups/{id}/entries' => [
        'GET' => fn() => $this->listGroupEntries($groupId),
        'POST' => fn() => $this->addGroupEntry($groupId),
      ],
      '/groups/{id}/entries/{subresourceId}' => [
        'PUT' => fn() => $this->updateGroupEntry($groupId, $subresourceId),
        'PATCH' => fn() => $this->updateGroupEntry($groupId, $subresourceId),
        'DELETE' => fn() => $this->removeGroupEntry($groupId, $subresourceId),
      ],
    ];

    if (!isset($table[$route])) {
      return abort_json(['error' => 'Not Found'], 404);
    }

    $handler = $table[$route][$method] ?? null;

    if ($handler) {
      return $handler();
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
   * GET /adminPermissions/groups/{id}: the full group record.
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
   * PUT|PATCH /adminPermissions/groups/{id}: edit a group's label and type.
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
   * DELETE /adminPermissions/groups/{id}: remove a group.
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
   * GET /adminPermissions/groups/{id}/members: the group's members,
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
   * POST /adminPermissions/groups/{id}/members: add one member.
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
   * DELETE /adminPermissions/groups/{id}/members/{userId}: drop a member.
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
      // vestigial scalar, must be 1: Authed/Authed_remote match on it
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
   * Whether `$type` comes from the instance's AuthHelper rather than the
   * built-in GLOBAL_GROUP_TYPES. Auth-helper groups match users on their
   * value entries; the built-ins never do (User matches member ids, the
   * rest match whole populations).
   */
  private function isAuthHelperGroupType(string $type): bool {
    return !isset(self::GLOBAL_GROUP_TYPES[$type]);
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
    // findById($id, true) will make new (unsaved) an Entity\User record with
    // the given remote id if nothing is found.
    /** @var ?Entity\User $remoteUser */
    $remoteUser = $this->authHelper->findById($remoteUserId, true)[0] ?? null;

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
   * GET /adminPermissions/groups/{id}/entries: a group's raw match values.
   */
  private function listGroupEntries(int $groupId): CI_Output {
    $group = $this->doctrine->em
      ->getRepository(InstanceGroup::class)
      ->findOneBy(['id' => $groupId, 'instance' => $this->instance]);

    if (!$group) {
      return abort_json(['error' => 'Group not found'], 404);
    }

    if (!$this->isAuthHelperGroupType($group->getGroupType())) {
      return abort_json(
        ['error' => 'Only auth-helper group types take entries'],
        422
      );
    }

    return render_json([
      'entries' => $group->getGroupValues()->toArray(),
    ]);
  }

  /**
   * POST /adminPermissions/groups/{id}/entries: add one match value.
   */
  private function addGroupEntry(int $groupId): CI_Output {
    $group = $this->doctrine->em
      ->getRepository(InstanceGroup::class)
      ->findOneBy(['id' => $groupId, 'instance' => $this->instance]);

    if (!$group) {
      return abort_json(['error' => 'Group not found'], 404);
    }

    if (!$this->isAuthHelperGroupType($group->getGroupType())) {
      return abort_json(
        ['error' => 'Only auth-helper group types take entries'],
        422
      );
    }

    try {
      $validated = V::validate($this->requestBody(), [
        'value' => [V::required(), V::string(), V::maxLength(255)],
      ]);
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    $entry = new Entity\GroupEntry();
    $entry->setGroupValue($validated['value']);
    $group->addGroupValue($entry);

    $this->doctrine->em->flush();
    $this->clearUserCache();

    return render_json(['entry' => $entry], 201);
  }

  /**
   * PUT|PATCH /adminPermissions/groups/{id}/entries/{entryId}: edit one
   * match value in place.
   */
  private function updateGroupEntry(int $groupId, int $entryId): CI_Output {
    $group = $this->doctrine->em
      ->getRepository(InstanceGroup::class)
      ->findOneBy(['id' => $groupId, 'instance' => $this->instance]);

    if (!$group) {
      return abort_json(['error' => 'Group not found'], 404);
    }

    if (!$this->isAuthHelperGroupType($group->getGroupType())) {
      return abort_json(
        ['error' => 'Only auth-helper group types take entries'],
        422
      );
    }

    $entry = $this->findEntryInGroup($group, $entryId);
    if (!$entry) {
      return abort_json(['error' => 'Entry not found'], 404);
    }

    try {
      $validated = V::validate($this->requestBody(), [
        'value' => [V::required(), V::string(), V::maxLength(255)],
      ]);
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    $entry->setGroupValue($validated['value']);

    $this->doctrine->em->flush();
    $this->clearUserCache();

    return render_json(['entry' => $entry]);
  }

  /**
   * DELETE /adminPermissions/groups/{id}/entries/{entryId}: drop one
   * match value.
   */
  private function removeGroupEntry(int $groupId, int $entryId): CI_Output {
    $group = $this->doctrine->em
      ->getRepository(InstanceGroup::class)
      ->findOneBy(['id' => $groupId, 'instance' => $this->instance]);

    if (!$group) {
      return abort_json(['error' => 'Group not found'], 404);
    }

    if (!$this->isAuthHelperGroupType($group->getGroupType())) {
      return abort_json(
        ['error' => 'Only auth-helper group types take entries'],
        422
      );
    }

    $entry = $this->findEntryInGroup($group, $entryId);
    if (!$entry) {
      return abort_json(['error' => 'Entry not found'], 404);
    }

    $group->removeGroupValue($entry); // orphanRemoval deletes on flush
    $this->doctrine->em->flush();
    $this->clearUserCache();

    return render_json(['removed' => $entryId]);
  }

  /**
   * Find the entry with `$entryId` among `$group`'s own entries.
   *
   * Entry ids are global, so fetching one straight from the repository
   * would let an admin address an entry belonging to another group, or
   * another instance. Scanning the group's collection enforces ownership.
   * The scan is cheap: the collection loads in one query and groups hold
   * few entries.
   *
   * @return ?Entity\GroupEntry null when the group has no such entry
   */
  private function findEntryInGroup(
    InstanceGroup $group,
    int $entryId
  ): ?Entity\GroupEntry {
    foreach ($group->getGroupValues() as $candidate) {
      if ($candidate->getId() === $entryId) {
        return $candidate;
      }
    }
    return null;
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

  /**
   * REST entry point for /adminPermissions/instanceGrants[/{id}].
   */
  public function instanceGrants($grantId = null) {
    $this->abortUnlessAdmin();

    $method = $this->input->server('REQUEST_METHOD');

    if ($grantId !== null) {
      $grantId = filter_var($grantId, FILTER_VALIDATE_INT);
      if ($grantId === false) {
        return abort_json(['error' => 'Invalid ID'], 400);
      }
    }

    $route = $grantId === null
      ? '/instanceGrants'
      : '/instanceGrants/{id}';

    $table = [
      '/instanceGrants' => [
        'GET' => fn() => $this->listInstanceGrants(),
        'POST' => fn() => $this->createInstanceGrant(),
      ],
      '/instanceGrants/{id}' => [
        'PUT' => fn() => $this->updateInstanceGrant($grantId),
        'PATCH' => fn() => $this->updateInstanceGrant($grantId),
        'DELETE' => fn() => $this->deleteInstanceGrant($grantId),
      ],
    ];

    $handler = $table[$route][$method] ?? null;

    if ($handler) {
      return $handler();
    }

    return abort_json(['error' => 'Method Not Allowed'], 405);
  }

  /**
   * REST entry point for /adminPermissions/collectionGrants[/{id}].
   */
  public function collectionGrants($grantId = null) {
    $this->abortUnlessAdmin();

    $method = $this->input->server('REQUEST_METHOD');

    if ($grantId !== null) {
      $grantId = filter_var($grantId, FILTER_VALIDATE_INT);
      if ($grantId === false) {
        return abort_json(['error' => 'Invalid ID'], 400);
      }
    }

    $route = $grantId === null
      ? '/collectionGrants'
      : '/collectionGrants/{id}';

    $table = [
      '/collectionGrants' => [
        'GET' => fn() => $this->listCollectionGrants(),
        'POST' => fn() => $this->createCollectionGrant(),
      ],
      '/collectionGrants/{id}' => [
        'PUT' => fn() => $this->updateCollectionGrant($grantId),
        'PATCH' => fn() => $this->updateCollectionGrant($grantId),
        'DELETE' => fn() => $this->deleteCollectionGrant($grantId),
      ],
    ];

    $handler = $table[$route][$method] ?? null;

    if ($handler) {
      return $handler();
    }

    return abort_json(['error' => 'Method Not Allowed'], 405);
  }

  private function listInstanceGrants() {
    $grants = $this->em
      ->getRepository(InstancePermission::class)
      ->findBy(['instance' => $this->instance]);

    return render_json(['instanceGrants' => $grants]);
  }


  /**
   * POST /adminPermissions/instanceGrants
   */
  private function createInstanceGrant(): CI_Output {
    try {
      $validated = V::validate($this->requestBody(), [
        'groupId' => [V::required(), V::integer()],
        'permissionLevelId' => [V::required(), V::integer()],
      ]);
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    $group = $this->em
      ->getRepository(InstanceGroup::class)
      ->findOneBy([
        'id' => (int) $validated['groupId'],
        'instance' => $this->instance,
      ]);
    if (!$group) {
      return abort_json(['error' => 'Group not found'], 422);
    }

    $level = $this->em
      ->find(Permission::class, (int) $validated['permissionLevelId']);
    if (!$level) {
      return abort_json(['error' => 'Permission level not found'], 422);
    }

    $existingGrant = $this->em
      ->getRepository(InstancePermission::class)
      ->findOneBy(['group' => $group, 'instance' => $this->instance]);
    if ($existingGrant) {
      return abort_json([
        'error' => 'Group already has an instance grant',
        'existingGrantId' => $existingGrant->getId(),
      ], 409);
    }

    $grant = new InstancePermission();
    $grant->setGroup($group);
    $grant->setInstance($this->instance);
    $grant->setPermission($level);

    $this->em->persist($grant);
    $this->em->flush();

    $this->clearUserCache();

    return render_json(['instanceGrant' => $grant], 201);
  }

  /**
   * PUT|PATCH /adminPermissions/instanceGrants/{id}
   */
  private function updateInstanceGrant(int $grantId): CI_Output {
    $grant = $this->em
      ->getRepository(InstancePermission::class)
      ->findOneBy(['id' => $grantId, 'instance' => $this->instance]);
    if (!$grant) {
      return abort_json(['error' => 'Grant not found'], 404);
    }

    try {
      $validated = V::validate($this->requestBody(), [
        'groupId' => [V::required(), V::integer()],
        'permissionLevelId' => [V::required(), V::integer()],
      ]);
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    $group = $this->em
      ->getRepository(InstanceGroup::class)
      ->findOneBy([
        'id' => (int) $validated['groupId'],
        'instance' => $this->instance,
      ]);
    if (!$group) {
      return abort_json(['error' => 'Group not found'], 422);
    }

    $level = $this->em
      ->find(Permission::class, (int) $validated['permissionLevelId']);
    if (!$level) {
      return abort_json(['error' => 'Permission level not found'], 422);
    }

    $existingGrant = $this->em
      ->getRepository(InstancePermission::class)
      ->findOneBy(['group' => $group, 'instance' => $this->instance]);
    if ($existingGrant && $existingGrant->getId() !== $grantId) {
      return abort_json([
        'error' => 'Group already has an instance grant',
        'existingGrantId' => $existingGrant->getId(),
      ], 409);
    }

    $grant->setGroup($group);
    $grant->setPermission($level);
    $this->em->flush();

    $this->clearUserCache();

    return render_json(['instanceGrant' => $grant]);
  }

  /**
   * DELETE /adminPermissions/instanceGrants/{id}: remove one grant.
   * The group itself is untouched.
   */
  private function deleteInstanceGrant(int $grantId): CI_Output {
    $grant = $this->em
      ->getRepository(InstancePermission::class)
      ->findOneBy(['id' => $grantId, 'instance' => $this->instance]);
    if (!$grant) {
      return abort_json(['error' => 'Grant not found'], 404);
    }

    $this->em->remove($grant);
    $this->em->flush();

    $this->clearUserCache();

    return render_json(['deleted' => $grantId]);
  }

  /**
   * GET /adminPermissions/collectionGrants
   */
  private function listCollectionGrants(): CI_Output {
    $instanceCollections = $this->instance->getCollections()->toArray();

    // findBy with an empty array builds an IN () clause, so an instance
    // with no collections answers directly instead of querying
    if (count($instanceCollections) === 0) {
      return render_json(['collectionGrants' => []]);
    }

    $grants = $this->em
      ->getRepository(CollectionPermission::class)
      ->findBy(['collection' => $instanceCollections]);

    return render_json(['collectionGrants' => $grants]);
  }

  /**
   * POST /adminPermissions/collectionGrants
   */
  private function createCollectionGrant(): CI_Output {
    try {
      $validated = V::validate($this->requestBody(), [
        'collectionId' => [V::required(), V::integer()],
        'groupId' => [V::required(), V::integer()],
        'permissionLevelId' => [V::required(), V::integer()],
      ]);
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    $group = $this->em
      ->getRepository(InstanceGroup::class)
      ->findOneBy([
        'id' => (int) $validated['groupId'],
        'instance' => $this->instance,
      ]);
    if (!$group) {
      return abort_json(['error' => 'Group not found'], 422);
    }

    $level = $this->em
      ->find(Permission::class, (int) $validated['permissionLevelId']);
    if (!$level) {
      return abort_json(['error' => 'Permission level not found'], 422);
    }

    $collection = $this->findCollectionInInstance(
      (int) $validated['collectionId']
    );
    if (!$collection) {
      return abort_json(['error' => 'Collection not found'], 422);
    }

    $existingGrant = $this->em
      ->getRepository(CollectionPermission::class)
      ->findOneBy(['group' => $group, 'collection' => $collection]);
    if ($existingGrant) {
      return abort_json([
        'error' => 'Group already has a grant on this collection',
        'existingGrantId' => $existingGrant->getId(),
      ], 409);
    }

    $grant = new CollectionPermission();
    $grant->setGroup($group);
    $grant->setCollection($collection);
    $grant->setPermission($level);

    $this->em->persist($grant);
    $this->em->flush();

    $this->clearUserCache();

    return render_json(['collectionGrant' => $grant], 201);
  }

  /**
   * Find the collection with `$collectionId` among this instance's own
   * collections.
   *
   * Collection ids are global, so fetching one straight from the
   * repository would let an admin reach another instance's collection.
   * Scanning the instance's collection list enforces membership, the
   * same ownership pattern as findEntryInGroup.
   *
   * @return ?Entity\Collection null when the instance has no such
   *   collection
   */
  private function findCollectionInInstance(int $collectionId): ?Entity\Collection {
    foreach ($this->instance->getCollections() as $candidate) {
      if ($candidate->getId() === $collectionId) {
        return $candidate;
      }
    }
    return null;
  }

  /**
   * PUT|PATCH /adminPermissions/collectionGrants/{id}
   */
  private function updateCollectionGrant(int $grantId): CI_Output {
    $grant = $this->em->find(CollectionPermission::class, $grantId);

    // Grant ids are global, so ownership comes from the grant's
    // collection belonging to this instance. An orphaned grant (null
    // collection) is unreachable for the same reason.
    $currentCollectionId = $grant?->getCollection()?->getId();
    if ($currentCollectionId === null || !$this->findCollectionInInstance($currentCollectionId)) {
      return abort_json(['error' => 'Grant not found'], 404);
    }

    try {
      $validated = V::validate($this->requestBody(), [
        'collectionId' => [V::required(), V::integer()],
        'groupId' => [V::required(), V::integer()],
        'permissionLevelId' => [V::required(), V::integer()],
      ]);
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    $group = $this->em
      ->getRepository(InstanceGroup::class)
      ->findOneBy([
        'id' => (int) $validated['groupId'],
        'instance' => $this->instance,
      ]);
    if (!$group) {
      return abort_json(['error' => 'Group not found'], 422);
    }

    $level = $this->em
      ->find(Permission::class, (int) $validated['permissionLevelId']);
    if (!$level) {
      return abort_json(['error' => 'Permission level not found'], 422);
    }

    $collection = $this->findCollectionInInstance(
      (int) $validated['collectionId']
    );
    if (!$collection) {
      return abort_json(['error' => 'Collection not found'], 422);
    }

    $existingGrant = $this->em
      ->getRepository(CollectionPermission::class)
      ->findOneBy(['group' => $group, 'collection' => $collection]);
    if ($existingGrant && $existingGrant->getId() !== $grantId) {
      return abort_json([
        'error' => 'Group already has a grant on this collection',
        'existingGrantId' => $existingGrant->getId(),
      ], 409);
    }

    $grant->setGroup($group);
    $grant->setCollection($collection);
    $grant->setPermission($level);
    $this->em->flush();

    $this->clearUserCache();

    return render_json(['collectionGrant' => $grant]);
  }

  /**
   * DELETE /adminPermissions/collectionGrants/{id}: remove one grant.
   */
  private function deleteCollectionGrant(int $grantId): CI_Output {
    $grant = $this->em->find(CollectionPermission::class, $grantId);

    // same ownership rule as updateCollectionGrant: reachable only
    // through a collection belonging to this instance
    $collectionId = $grant?->getCollection()?->getId();
    if ($collectionId === null || !$this->findCollectionInInstance($collectionId)) {
      return abort_json(['error' => 'Grant not found'], 404);
    }

    $this->em->remove($grant);
    $this->em->flush();

    $this->clearUserCache();

    return render_json(['deleted' => $grantId]);
  }
}
