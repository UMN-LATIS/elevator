<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

use Doctrine\ORM\EntityManager;
use Entity\Drawer;
use Entity\DrawerGroup;
use Entity\DrawerPermission;
use Entity\GroupEntry;
use Entity\Permission;
use Entity\User;
use SimpleValidator as V;

/**
 * JSON api for the drawer group/permission management ui.
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
   * GET /drawerPermissions/groupTypes
   * List of group types the signed-in user can create.
   * Some are admin-only.
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
   * GET /drawerPermissions/userAutocomplete?q=...
   * suggests people for a "Specific People" group's member field. The twin of
   * AdminPermissions::userAutocomplete, except open to any drawer manager
   * instead of instance admins only.
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
   * GET /drawerPermissions/manageableDrawers
   * Drawers the signed-in user can manage, for the management page's Drawers
   * tab and the rule editor's drawer picker.
   */
  public function manageableDrawers(): CI_Output {
    $this->abortUnlessCanManageDrawers();

    if ($this->input->server('REQUEST_METHOD') !== 'GET') {
      return abort_json(['error' => 'Method Not Allowed'], 405);
    }

    $drawerPayload = array_map(
      fn(Drawer $drawer) => [
        'id' => $drawer->getId(),
        'title' => $drawer->getTitle(),
      ],
      $this->getManageableDrawers()
    );

    return render_json(['manageableDrawers' => $drawerPayload]);
  }

  /**
   * /drawerPermissions/grants[/{id}].
   *
   * Entry point for ops on a permission grant (aka rule).
   * A grant is a combination of a drawer, drawergroup, and permission level.
   *
   * A user with PERM_CREATEDRAWERS level access on a given drawer
   * can re-level any grant on that drawer, **including grants for groups owned
   * by other users**. (Same as Permissions::edit under DRAWER_PERMISSION.)
   *
   * Deleting is the exception: it needs the group to be the caller's own.
   * See deleteGrant.
   */
  public function grants($grantId = null): CI_Output {
    $this->abortUnlessCanManageDrawers();

    $method = $this->input->server('REQUEST_METHOD');

    $grantId = $grantId === null
      ? null
      : filter_var($grantId, FILTER_VALIDATE_INT);

    // a non-numeric id segment becomes false
    if ($grantId === false) {
      return abort_json(['error' => 'Invalid ID'], 400);
    }

    $route = $grantId === null ? '/grants' : '/grants/{id}';

    $table = [
      '/grants' => [
        'GET' => fn() => $this->listGrants(),
        'POST' => fn() => $this->createGrant(),
      ],
      '/grants/{id}' => [
        'PUT' => fn() => $this->updateGrant($grantId),
        'PATCH' => fn() => $this->updateGrant($grantId),
        'DELETE' => fn() => $this->deleteGrant($grantId),
      ],
    ];

    $handler = $table[$route][$method] ?? null;

    if ($handler) {
      return $handler();
    }

    return abort_json(['error' => 'Method Not Allowed'], 405);
  }

  /**
   * GET /drawerPermissions/grants: every grant on every drawer the
   * caller can manage, for the management page's Rules tab.
   */
  private function listGrants(): CI_Output {
    // grantPayload reads each grant's group and that group's owner, so
    // select both here rather than let Doctrine lazy-load them one grant
    // at a time. Left joins: a grant can outlive its group, and a global
    // group type has no owner.
    $query = $this->em
      ->getRepository(DrawerPermission::class)
      ->createQueryBuilder('drawerGrant')
      ->addSelect('grantGroup', 'owner')
      ->join('drawerGrant.drawer', 'drawer')
      ->leftJoin('drawerGrant.group', 'grantGroup')
      ->leftJoin('grantGroup.user', 'owner')
      ->where('drawer.instance = :instance')
      ->setParameter('instance', $this->instance);

    // if user doesn't manage every drawer (non-admin), limit to only
    // the drawers they manage
    if (!$this->canManageEveryDrawer()) {
      $manageableDrawerIds = $this->manageableDrawerIdsFromPermissionMap();

      // bail early if user manages no drawers
      if (count($manageableDrawerIds) === 0) {
        return render_json(['grants' => []]);
      }
      $query
        ->andWhere('drawer.id IN (:manageableDrawerIds)')
        ->setParameter('manageableDrawerIds', $manageableDrawerIds);
    }

    return render_json([
      'grants' => array_map(
        fn(DrawerPermission $grant) => $this->grantPayload($grant),
        $query->getQuery()->getResult()
      ),
    ]);
  }

  /**
   * POST /drawerPermissions/grants: give one of the caller's own groups
   * a permission level on a drawer they manage.
   */
  private function createGrant(): CI_Output {
    try {
      $validated = V::validate($this->requestBody(), [
        'drawerId' => [V::required(), V::integer()],
        'drawerGroupId' => [V::required(), V::integer()],
        'permissionLevelId' => [V::required(), V::integer()],
      ]);
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    $drawer = $this->findManageableDrawerOrAbort((int) $validated['drawerId']);

    // sharing is limited to the caller's own groups
    $group = $this->findCurrentUserDrawerGroup((int) $validated['drawerGroupId']);
    if (!$group) {
      return abort_json(['error' => 'Group not found'], 422);
    }

    $level = $this->em->find(Permission::class, (int) $validated['permissionLevelId']);
    if (!$level) {
      return abort_json(['error' => 'Permission level not found'], 422);
    }

    $existing = $this->findDrawerPermissionForGroup($drawer, $group);
    if ($existing) {
      return abort_json([
        'error' => 'Group already has a grant on this drawer',
        'existingGrantId' => $existing->getId(),
      ], 409);
    }

    $grant = new DrawerPermission();
    $grant->setDrawer($drawer);
    $grant->setGroup($group);
    $grant->setPermission($level);
    $this->em->persist($grant);

    // Access computation reads DrawerPermission, but legacy listing paths
    // still read the drawergroup_drawer M2M, so keep them in sync,
    // matching Drawers::addDrawer.
    if (!$this->groupLinksDrawer($group, $drawer)) {
      $group->addDrawer($drawer);
    }

    $this->em->flush();

    // the group can match arbitrary users, so their cached permissions
    // are stale, not just the owner's
    $this->clearAllUserCache();

    return render_json(['grant' => $this->grantPayload($grant)], 201);
  }

  /**
   * PUT|PATCH /drawerPermissions/grants/{id}: change a grant's level.
   * Drawer manage access is the whole gate, so grants for other owners'
   * groups are editable too.
   */
  private function updateGrant(int $grantId): CI_Output {
    $grant = $this->findManageableGrantOrAbort($grantId);

    try {
      $validated = V::validate($this->requestBody(), [
        'permissionLevelId' => [V::required(), V::integer()],
      ]);
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    $level = $this->em->find(Permission::class, (int) $validated['permissionLevelId']);
    if (!$level) {
      return abort_json(['error' => 'Permission level not found'], 422);
    }

    // re-level only, the M2M link already exists and stays put
    $grant->setPermission($level);
    $this->em->flush();
    $this->clearAllUserCache();

    return render_json(['grant' => $this->grantPayload($grant)]);
  }

  /**
   * DELETE /drawerPermissions/grants/{id}: revoke a grant, which drawer
   * manage access alone does not permit. Unlike updateGrant, the group
   * must be the caller's own.
   */
  private function deleteGrant(int $grantId): CI_Output {
    $grant = $this->findManageableGrantOrAbort($grantId);

    $drawer = $grant->getDrawer();
    $group = $grant->getGroup();

    // Deleting is one-way: createGrant takes only the caller's own
    // groups, so a grant deleted off someone else's group could not be
    // put back by the manager who deleted it. Re-levelling it to
    // PERM_NOPERM revokes the access and leaves the grant for its owner,
    // so that is the path the Rules tab offers instead. This is a
    // deliberate break from Permissions::edit, which lets a drawer
    // manager delete any grant on their drawer.
    // A grant that outlived its group belongs to nobody, so it stays
    // deletable as cleanup.
    if ($group !== null && !$this->isOwnGroup($group)) {
      return abort_json(
        ['error' => "Cannot delete another owner's grant"],
        403
      );
    }

    $this->em->remove($grant);

    // The drawergroup_drawer link exists so the group can reach the drawer.
    // Drop it only when no other grant keeps that true. A stray duplicate
    // grant (no unique constraint guards concurrent creates) would otherwise
    // leave a DrawerPermission with its M2M link gone.
    if ($group && !$this->groupHasOtherGrantOnDrawer($group, $drawer, $grant)) {
      $group->removeDrawer($drawer);
    }

    $this->em->flush();
    $this->clearAllUserCache();

    return render_json(['removed' => $grantId]);
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
   * One grant as the Rules tab consumes it: drawerId and
   * permissionLevelId are id references the caller joins against
   * /drawers and the permission level catalog. The group rides along
   * inline because /groups only lists the caller's own groups, so a
   * grant for another owner's group could not be joined client-side.
   */
  private function grantPayload(DrawerPermission $grant): array {
    $group = $grant->getGroup();

    $groupPayload = null;
    if ($group !== null) {
      $groupPayload = [
        'id' => $group->getId(),
        'label' => $group->getGroupLabel(),
        'type' => $group->getGroupType(),
        'ownedByCurrentUser' => $this->isOwnGroup($group),
        // null for a global group type, which has no owner
        'ownerName' => $group->getUser()?->getDisplayName(),
      ];
    }

    return [
      'id' => $grant->getId(),
      'drawerId' => $grant->getDrawer()?->getId(),
      'permissionLevelId' => $grant->getPermission()?->getId(),
      'group' => $groupPayload,
    ];
  }

  /**
   * The drawer with `$drawerId` in this instance, aborting 404 when it
   * does not exist and 403 when the caller cannot manage it.
   */
  private function findManageableDrawerOrAbort(int $drawerId): Drawer {
    $drawer = $this->em
      ->getRepository(Drawer::class)
      ->findOneBy(['id' => $drawerId, 'instance' => $this->instance]);
    if (!$drawer) {
      abort_json(['error' => 'Drawer not found'], 404);
    }

    if ($this->user_model->getAccessLevel(DRAWER_PERMISSION, $drawer) < PERM_CREATEDRAWERS) {
      abort_json(['error' => 'Forbidden'], 403);
    }

    return $drawer;
  }

  /**
   * Find the grant for `$group` on `$drawer`, scanning the drawer's own
   * permissions so a request cannot address a grant on another drawer.
   */
  private function findDrawerPermissionForGroup(
    Drawer $drawer,
    DrawerGroup $group
  ): ?DrawerPermission {
    foreach ($drawer->getPermissions() as $candidate) {
      if ($candidate->getGroup()?->getId() === $group->getId()) {
        return $candidate;
      }
    }
    return null;
  }

  /**
   * The grant with `$grantId`, aborting 404 when it does not exist in
   * this instance and 403 when the caller cannot manage its drawer.
   */
  private function findManageableGrantOrAbort(int $grantId): DrawerPermission {
    $grant = $this->em->find(DrawerPermission::class, $grantId);

    $drawer = $grant?->getDrawer();
    if (!$drawer) {
      abort_json(['error' => 'Grant not found'], 404);
    }

    // the drawer gate owns both the instance scope and the manage level,
    // so a grant on another instance's drawer aborts 404 there
    $this->findManageableDrawerOrAbort($drawer->getId());

    return $grant;
  }

  /**
   * Whether `$group` still holds a grant on `$drawer` other than
   * `$excluding`, so delete keeps the M2M link when a stray duplicate
   * grant remains.
   */
  private function groupHasOtherGrantOnDrawer(
    DrawerGroup $group,
    Drawer $drawer,
    DrawerPermission $excluding
  ): bool {
    foreach ($drawer->getPermissions() as $candidate) {
      if ($candidate->getId() === $excluding->getId()) {
        continue;
      }
      if ($candidate->getGroup()?->getId() === $group->getId()) {
        return true;
      }
    }
    return false;
  }

  /**
   * Whether the group already links the drawer through the
   * drawergroup_drawer M2M, so create adds the link only once.
   */
  private function groupLinksDrawer(DrawerGroup $group, Drawer $drawer): bool {
    foreach ($group->getDrawer() as $candidate) {
      if ($candidate->getId() === $drawer->getId()) {
        return true;
      }
    }
    return false;
  }

  private function isOwnGroup(?DrawerGroup $group): bool {
    $ownerId = $group?->getUser()?->getId();
    // compare entity ids on both sides: user_model->userId comes from the
    // session as a string, so it would never === an int id
    return $ownerId !== null && $ownerId === $this->user_model->user?->getId();
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
   * mutation, aborting with 404 when it does not exist.
   */
  private function findEditableGroupOrAbort(int $groupId): DrawerGroup {
    $group = $this->findCurrentUserDrawerGroup($groupId);
    if (!$group) {
      abort_json(['error' => 'Group not found'], 404);
    }

    return $group;
  }

  /**
   * The auto-created "personal" drawer group: a User-type group that
   * includes the owner's own id as a member entry, created
   * the first time a user makes a drawer.
   */
  private function getPersonalDrawerGroup(): ?DrawerGroup {
    $ownUserId = $this->user_model?->userId;

    if (!$ownUserId) {
      return null;
    }

    // heuristic: the oldest User-type group the user owns that lists
    // the user's own id as a member and is named after the user, matching
    // how Drawers.php labels the group it auto-creates
    $query = $this->em
      ->getRepository(DrawerGroup::class)
      ->createQueryBuilder('drawerGroup')
      ->join('drawerGroup.group_values', 'entry')
      ->where('drawerGroup.user = :ownUserId')
      ->andWhere('drawerGroup.group_type = :groupType')
      ->andWhere('entry.groupValue = :ownUserId')
      ->andWhere('drawerGroup.group_label = :ownDisplayName')
      ->setParameter('ownUserId', $ownUserId)
      ->setParameter('groupType', USER_TYPE)
      ->setParameter('ownDisplayName', $this->user_model->getDisplayName())
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
   * Whether the caller manages every drawer in the instance.
   *
   * Instance admins hold PERM_ADMIN on every drawer in their instance
   * through a fallback in getAccessLevel, not through the
   * drawerPermissions map, so no id list describes their reach.
   */
  private function canManageEveryDrawer(): bool {
    return $this->user_model->isInstanceAdmin()
      || $this->user_model->getIsSuperAdmin();
  }

  /**
   * Ids of the drawers the caller holds at least PERM_CREATEDRAWERS on.
   * Empty for an instance admin, whose reach comes from the
   * getAccessLevel fallback instead of the map.
   */
  private function manageableDrawerIdsFromPermissionMap(): array {
    // $this->user_model->drawerPermissions is an assoc. array of
    // [drawerId => accessLevel], so pluck the ids at manage level
    return array_keys(array_filter(
      $this->user_model->drawerPermissions,
      fn($level) => $level >= PERM_CREATEDRAWERS
    ));
  }

  /**
   * Drawers the signed-in user holds at least PERM_CREATEDRAWERS on,
   * sorted by title.
   */
  private function getManageableDrawers(): array {
    $drawerRepository = $this->em->getRepository(Drawer::class);

    if ($this->canManageEveryDrawer()) {
      return $drawerRepository->findBy(
        ['instance' => $this->instance],
        ['title' => 'ASC']
      );
    }

    $manageableDrawerIds = $this->manageableDrawerIdsFromPermissionMap();
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
