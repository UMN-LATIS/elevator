<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

use Doctrine\ORM\EntityManager;
use Entity\Drawer;
use Entity\DrawerGroup;
use Entity\GroupEntry;
use Entity\User;
use SimpleValidator as V;

/**
 * JSON api for the drawer group/permission management ui.
 *
 * Drawer groups are owned by a user, unlike instance groups which belong
 * to the instance. So every action is scoped to the signed-in user's own
 * groups and gated on "can manage drawers", not instance admin.
 */
class DrawerPermissions extends Instance_Controller {
  private AuthHelper $authHelper;
  private GroupTypeCatalog $groupTypeCatalog;
  private EntityManager $em;

  public function __construct() {
    parent::__construct();
    $this->load->library('SimpleValidator');
    $this->authHelper = $this->user_model->getAuthHelper();
    $this->groupTypeCatalog = new GroupTypeCatalog($this->authHelper->authTypes);
    $this->em = $this->doctrine->em;
  }

  /**
   * GET /drawerPermissions/groupTypes: the catalog for the type selector.
   * Admin-only types stay in the list so non-admin UIs can show them
   * disabled, matching the legacy form.
   */
  public function groupTypes(): CI_Output {
    $this->abortUnlessCanManageDrawers();

    $groupTypes = array_map(
      fn($type) => [
        "type" => $type["name"],
        "label" => $type["label"],
        "description" => $type["helpText"] ?? "",
        "entryHints" => $this->groupTypeCatalog->entryHintsFor(
          $type["name"],
          $this->user_model->userData
        ),
        "adminOnly" => $this->groupTypeCatalog->isAdminOnly($type["name"]),
      ],
      array_values($this->groupTypeCatalog->all())
    );

    return render_json(["groupTypes" => $groupTypes]);
  }

  /**
   * GET /drawerPermissions/userAutocomplete?q=... suggests people for a
   * "Specific People" group's member field. Read only. The twin of
   * AdminPermissions::userAutocomplete, open to any drawer manager
   * instead of instance admins only.
   *
   * Every school searches local users. Schools with an external directory
   * (UMN, St. Olaf) also return matches from central auth.
   */
  public function userAutocomplete(): CI_Output {
    $this->abortUnlessCanManageDrawers();

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
   * GET /drawerPermissions/drawers: drawers the signed-in user can
   * manage, for the management page's Drawers tab.
   */
  public function drawers(): CI_Output {
    $this->abortUnlessCanManageDrawers();

    $drawerPayload = array_map(
      fn(Drawer $drawer) => [
        'id' => $drawer->getId(),
        'title' => $drawer->getTitle(),
      ],
      $this->manageableDrawers()
    );

    return render_json(['drawers' => $drawerPayload]);
  }

  /**
   * REST entry point for /drawerPermissions/groups[/{id}] and its
   * members/entries subresources.
   *
   * Trailing URL segments arrive as method args, so a request to
   * /drawerPermissions/groups/5/members/2 calls this with
   * $groupId = "5", $subresource = "members", $subresourceId = "2".
   */
  public function groups($groupId = null, $subresource = null, $subresourceId = null): CI_Output {
    $this->abortUnlessCanManageDrawers();

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

    return abort_json(['error' => 'Method Not Allowed'], 405);
  }

  private function listGroups(): CI_Output {
    $personalGroupId = $this->getPersonalDrawerGroup()?->getId();

    $groups = $this->em
      ->getRepository(DrawerGroup::class)
      ->findBy(['user' => $this->user_model->user]);


    $withPersonalFlag = fn(DrawerGroup $group): array =>
    $group->jsonSerialize() + ['is_personal' => $group->getId() === $personalGroupId];

    return render_json(['groups' => array_map($withPersonalFlag, $groups)]);
  }

  /**
   * GET /drawerPermissions/groups/{id}: one of the user's own groups.
   */
  private function showGroup(int $groupId): CI_Output {
    $group = $this->findEditableGroupOrAbort($groupId);

    return render_json(['group' => $group]);
  }

  /**
   * POST /drawerPermissions/groups: create an empty group owned by the
   * signed-in user. Members and match values are added separately.
   */
  private function createGroup(): CI_Output {
    try {
      $validated = V::validate(
        $this->requestBody(),
        $this->groupAttributeRules()
      );
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    $type = $validated['type'];
    if (!$this->canUseGroupType($type)) {
      return abort_json(
        ['error' => 'Only instance admins can use instance-wide group types'],
        403
      );
    }

    $group = new DrawerGroup();
    $group->setUser($this->user_model->user);
    $group->setGroupType($type);
    $group->setGroupLabel($validated['label']);

    if ($this->groupTypeCatalog->ignoresGroupValues($type)) {
      // must be 1 for legacy reasons
      $group->setGroupValue(1);
    } else {
      $group->setGroupValue(null);
    }

    $this->em->persist($group);
    $this->em->flush();

    $this->removeCurrentUserCache();

    return render_json(['group' => $group], 201);
  }

  /**
   * PUT|PATCH /drawerPermissions/groups/{id}: edit a group's label and
   * type.
   *
   * Changing the type clears existing entries, since they belong to the
   * old type. Editing only the label leaves them alone.
   */
  private function updateGroup(int $groupId): CI_Output {
    $group = $this->findEditableGroupOrAbort($groupId);

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

    // a non-admin may keep an admin-only type it already has (rename
    // only), but may not switch a group onto one
    if ($hasTypeChanged && !$this->canUseGroupType($newType)) {
      return abort_json(
        ['error' => 'Only instance admins can use instance-wide group types'],
        403
      );
    }

    $group->setGroupLabel($validated['label']);

    if ($hasTypeChanged) {
      $group->setGroupType($newType);

      // toArray() copies first so removing entries does not mutate the
      // list mid-loop
      foreach ($group->getGroupValues()->toArray() as $entry) {
        $group->removeGroupValue($entry);
      }
      $group->setGroupValue($this->groupTypeCatalog->ignoresGroupValues($newType) ? 1 : null);
    }

    $this->em->flush();

    if ($hasTypeChanged) {
      // clearing the members revokes their drawer access, so their
      // cached permissions are stale too, not just the owner's
      $this->clearAllUserCache();
    } else {
      $this->removeCurrentUserCache();
    }

    return render_json(['group' => $group]);
  }

  /**
   * DELETE /drawerPermissions/groups/{id}: remove one of the user's groups.
   */
  private function deleteGroup(int $groupId): CI_Output {
    $group = $this->findEditableGroupOrAbort($groupId);

    $this->em->remove($group);
    $this->em->flush();

    // deleting a group cascades to its members and entries, changing
    // many users' permissions, so clear every user's cache
    $this->clearAllUserCache();

    return render_json(['deleted' => $groupId]);
  }

  /**
   * GET /drawerPermissions/groups/{id}/members: the group's members,
   * resolved to names so the UI can show who belongs.
   */
  private function listGroupMembers(int $groupId): CI_Output {
    $group = $this->findEditableGroupOrAbort($groupId);

    return render_json(['members' => $this->resolveMembers($group)]);
  }

  /**
   * POST /drawerPermissions/groups/{id}/members: add one member.
   *
   * Exactly one of two fields per request: `localUserId` for someone who
   * already has a local row, or `remoteUserId` (a netid/username) for someone
   * not local yet, who we provision on the spot via firstOrProvisionRemoteUser.
   */
  private function addGroupMember(int $groupId): CI_Output {
    $group = $this->findEditableGroupOrAbort($groupId);

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
      $user = $this->em
        ->getRepository(User::class)
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

    $entry = new GroupEntry();
    $entry->setGroupValue($userId);
    $group->addGroupValue($entry);

    $this->em->flush();

    // the new member's drawer access changed, so their cached
    // permissions are stale too, not just the owner's
    $this->clearAllUserCache();

    return render_json(['member' => $this->memberPayload($user)], 201);
  }

  /**
   * DELETE /drawerPermissions/groups/{id}/members/{userId}: drop a member.
   */
  private function removeGroupMember(int $groupId, int $userId): CI_Output {
    $group = $this->findEditableGroupOrAbort($groupId);

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
    $this->em->flush();

    // removal revokes the member's drawer access, so their cached
    // permissions are stale too, not just the owner's
    $this->clearAllUserCache();

    return render_json(['removed' => $userId]);
  }

  /**
   * GET /drawerPermissions/groups/{id}/entries: a group's raw match values.
   */
  private function listGroupEntries(int $groupId): CI_Output {
    $group = $this->findEditableGroupOrAbort($groupId);

    if (!$this->groupTypeCatalog->isAuthHelperType($group->getGroupType())) {
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
   * POST /drawerPermissions/groups/{id}/entries: add one match value.
   */
  private function addGroupEntry(int $groupId): CI_Output {
    $group = $this->findEditableGroupOrAbort($groupId);

    if (!$this->groupTypeCatalog->isAuthHelperType($group->getGroupType())) {
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

    $entry = new GroupEntry();
    $entry->setGroupValue($validated['value']);
    $group->addGroupValue($entry);

    $this->em->flush();

    // entries change which users the group matches, so arbitrary users'
    // cached permissions are stale
    $this->clearAllUserCache();

    return render_json(['entry' => $entry], 201);
  }

  /**
   * PUT|PATCH /drawerPermissions/groups/{id}/entries/{entryId}: edit one
   * match value in place.
   */
  private function updateGroupEntry(int $groupId, int $entryId): CI_Output {
    $group = $this->findEditableGroupOrAbort($groupId);

    if (!$this->groupTypeCatalog->isAuthHelperType($group->getGroupType())) {
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

    $this->em->flush();
    $this->clearAllUserCache();

    return render_json(['entry' => $entry]);
  }

  /**
   * DELETE /drawerPermissions/groups/{id}/entries/{entryId}: drop one
   * match value.
   */
  private function removeGroupEntry(int $groupId, int $entryId): CI_Output {
    $group = $this->findEditableGroupOrAbort($groupId);

    if (!$this->groupTypeCatalog->isAuthHelperType($group->getGroupType())) {
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
    $this->em->flush();
    $this->clearAllUserCache();

    return render_json(['removed' => $entryId]);
  }

  /**
   * Resolve a group's entries to member display data. Only User groups hold
   * user ids; other types store raw attribute strings and have no members.
   */
  private function resolveMembers(DrawerGroup $group): array {
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
    $users = $this->em
      ->getRepository(User::class)
      ->findBy(['id' => $userIds], ['displayName' => 'ASC']);

    return array_map(fn($user) => $this->memberPayload($user), $users);
  }

  private function memberPayload(User $user): array {
    return [
      'userId' => $user->getId(),
      'name' => $user->getDisplayName(),
      'email' => $user->getEmail(),
      'username' => $user->getUsername(),
      'userType' => $user->getUserType(),
      'createdAt' => $user->getCreatedAt()?->format('c'),
    ];
  }

  /**
   * Find the entry with `$entryId` among `$group`'s own entries.
   *
   * Entry ids are global, so fetching one straight from the repository
   * would let a user address an entry belonging to another group, or
   * another user's group. Scanning the group's collection enforces
   * ownership. The scan is cheap: the collection loads in one query and
   * groups hold few entries.
   *
   * @return ?GroupEntry null when the group has no such entry
   */
  private function findEntryInGroup(
    DrawerGroup $group,
    int $entryId
  ): ?GroupEntry {
    foreach ($group->getGroupValues() as $candidate) {
      if ($candidate->getId() === $entryId) {
        return $candidate;
      }
    }
    return null;
  }

  /**
   * Find one of the signed-in user's groups for a detail read or
   * mutation, aborting with 404 when it does not exist. The personal
   * group 404s too: the API pretends it does not exist rather than
   * revealing an unmodifiable group.
   */
  private function findEditableGroupOrAbort(int $groupId): DrawerGroup {
    $this->abortIfPersonalGroup($groupId);

    $group = $this->findCurrentUserDrawerGroup($groupId);
    if (!$group) {
      abort_json(['error' => 'Group not found'], 404);
    }

    return $group;
  }

  private function abortIfPersonalGroup(int $groupId): void {
    if ($groupId === $this->getPersonalDrawerGroup()?->getId()) {
      abort_json(['error' => 'Group not found'], 404);
    }
  }

  /**
   * The auto-created "personal" drawer group: a User-type group that
   * includes the owner's own id as a member entry, created in Drawers.php
   * the first time a user makes a drawer. It backs the user's access to
   * their own drawers, so the groups API hides it and refuses to mutate it.
   *
   * Returns null when the user has never created a drawer, so no personal
   * group exists yet.
   */
  private function getPersonalDrawerGroup(): ?DrawerGroup {
    $ownUserId = $this->user_model?->userId;

    if (!$ownUserId) {
      return null;
    }

    // heuristic: the oldest User-type group the user owns that lists
    // the user's own id as a member
    $query = $this->em
      ->getRepository(DrawerGroup::class)
      ->createQueryBuilder('drawerGroup')
      ->join('drawerGroup.group_values', 'entry')
      ->where('drawerGroup.user = :ownUserId')
      ->andWhere('drawerGroup.group_type = :groupType')
      ->andWhere('entry.groupValue = :ownUserId')
      ->setParameter('ownUserId', $ownUserId)
      ->setParameter('groupType', USER_TYPE)
      ->orderBy('drawerGroup.id', 'ASC')
      ->setMaxResults(1)
      ->getQuery();

    return $query->getOneOrNullResult();
  }

  private function findCurrentUserDrawerGroup(int $groupId): ?DrawerGroup {
    return $this->em
      ->getRepository(DrawerGroup::class)
      ->findOneBy([
        'id' => $groupId,
        'user' => $this->user_model->user,
      ]);
  }

  /**
   * Validation rules for a group's editable attributes (label + type).
   * The label rejects `< > "` to prevent HTML injection while still
   * allowing names like `R&D` and `Bob's Team`.
   */
  private function groupAttributeRules(): array {
    $validTypes = array_keys($this->groupTypeCatalog->all());
    return [
      'type' => [
        V::required(),
        fn($value) => !isset($value) || in_array($value, $validTypes, true)
          ? true
          : 'Unknown group type',
      ],
      'label' => [
        V::required(),
        V::maxLength(255),
        V::notRegex('/[<>"]/', 'Label cannot contain < > or " characters')
      ],
    ];
  }

  /**
   * Whether the signed-in user may put a group on `$type`:
   * population-wide types are reserved for instance admins.
   */
  private function canUseGroupType(string $type): bool {
    if (!$this->groupTypeCatalog->isAdminOnly($type)) {
      return true;
    }
    return $this->user_model->isInstanceAdmin()
      || $this->user_model->getIsSuperAdmin();
  }

  /**
   * Drawers the signed-in user holds at least PERM_CREATEDRAWERS on.
   *
   * Instance admins hold PERM_ADMIN on every drawer in their instance
   * through a fallback in getAccessLevel, not through the
   * drawerPermissions map, so they get the whole instance's drawers here.
   */
  private function manageableDrawers(): array {
    $drawerRepository = $this->em->getRepository(Drawer::class);

    $isEveryDrawerManageable = $this->user_model->isInstanceAdmin()
      || $this->user_model->getIsSuperAdmin();
    if ($isEveryDrawerManageable) {
      return $drawerRepository->findBy(
        ['instance' => $this->instance],
        ['title' => 'ASC']
      );
    }

    $manageableDrawerIds = array_keys(array_filter(
      $this->user_model->drawerPermissions,
      fn($level) => $level >= PERM_CREATEDRAWERS
    ));
    if (count($manageableDrawerIds) === 0) {
      return [];
    }

    return $drawerRepository->findBy(
      ['id' => $manageableDrawerIds, 'instance' => $this->instance],
      ['title' => 'ASC']
    );
  }

  /**
   * Abort unless the signed-in user can manage at least one drawer.
   */
  private function abortUnlessCanManageDrawers(): void {
    $this->abortUnlessAuthed();
    if (!$this->canManageDrawers()) {
      abort_json(['error' => 'Forbidden'], 403);
    }
  }

  private function canManageDrawers(): bool {
    if ($this->user_model->getIsSuperAdmin()) {
      return true;
    }
    if ($this->user_model->getAccessLevel('instance', $this->instance) >= PERM_CREATEDRAWERS) {
      return true;
    }
    // getMaxCollectionPermission returns null when the user has no
    // collection grants at all
    if (($this->user_model->getMaxCollectionPermission() ?? 0) >= PERM_CREATEDRAWERS) {
      return true;
    }
    // max() errors on an empty array, so seed a 0
    $drawerGrantLevels = array_values($this->user_model->drawerPermissions);
    return max([0, ...$drawerGrantLevels]) >= PERM_CREATEDRAWERS;
  }

  private function removeCurrentUserCache(): void {
    if (!$this->config->item('enableCaching')) return;
    $this->userCache->delete($this->user_model->userId);
  }

  private function clearAllUserCache(): void {
    if (!$this->config->item('enableCaching')) return;
    $this->userCache->clear();
  }
}
