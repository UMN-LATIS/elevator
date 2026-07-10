<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

use Doctrine\ORM\EntityManager;
use Entity\Drawer;
use Entity\DrawerGroup;
use SimpleValidator as V;

/**
 * JSON api for the drawer group/permission management ui.
 *
 * Drawer groups are owned by a user, unlike instance groups which belong
 * to the instance. So every action is scoped to the signed-in user's own
 * groups and gated on "can manage drawers", not instance admin.
 */
class DrawerPermissions extends Instance_Controller {
  private GroupTypeCatalog $groupTypeCatalog;
  private EntityManager $em;

  public function __construct() {
    parent::__construct();
    $this->load->library('SimpleValidator');
    $this->groupTypeCatalog = new GroupTypeCatalog(
      $this->user_model->getAuthHelper()->authTypes
    );
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
        // TODO: real hints, see AdminPermissions::entryHintsForType
        "entryHints" => [],
        "adminOnly" => $this->groupTypeCatalog->isAdminOnly($type["name"]),
      ],
      array_values($this->groupTypeCatalog->all())
    );

    return render_json(["groupTypes" => $groupTypes]);
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
   * REST entry point for /drawerPermissions/groups[/{id}].
   *
   * Trailing URL segments arrive as method args, so a request to
   * /drawerPermissions/groups/5 calls this with $groupId = "5".
   */
  public function groups($groupId = null): CI_Output {
    $this->abortUnlessCanManageDrawers();

    $method = $this->input->server('REQUEST_METHOD');

    $groupId = $groupId === null
      ? null
      : filter_var($groupId, FILTER_VALIDATE_INT);

    // a non-numeric id segment becomes false
    if ($groupId === false) {
      return abort_json(['error' => 'Invalid ID'], 400);
    }

    $route = '/groups';
    if ($groupId !== null) {
      $route .= '/{id}';
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
    $this->abortIfPersonalGroup($groupId);

    $group = $this->findCurrentUserDrawerGroup($groupId);
    if (!$group) {
      return abort_json(['error' => 'Group not found'], 404);
    }

    return render_json(['group' => $group]);
  }

  /**
   * POST /drawerPermissions/groups: create a group owned by the
   * signed-in user, with any initial member or match values.
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

      $nonEmptyValues = array_filter(
        (array) ($validated['values'] ?? []),
        fn($value) => $value !== ''
      );

      foreach ($nonEmptyValues as $value) {
        // a non-numeric User value is a remote username. Provision its
        // local row and store the resulting user id. Other types store
        // their value as-is.
        if ($type === USER_TYPE && !is_numeric($value)) {
          try {
            $value = $this->firstOrProvisionRemoteUser($value)->getId();
          } catch (RemoteUserNotFoundException $e) {
            return abort_json(['error' => $e->getMessage()], 404);
          }
        }

        $entry = new \Entity\GroupEntry();
        $entry->setGroupValue($value);
        $group->addGroupValue($entry);
      }
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
    $this->abortIfPersonalGroup($groupId);

    $group = $this->findCurrentUserDrawerGroup($groupId);
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
    $this->abortIfPersonalGroup($groupId);

    $group = $this->findCurrentUserDrawerGroup($groupId);
    if (!$group) {
      return abort_json(['error' => 'Group not found'], 404);
    }

    $this->em->remove($group);
    $this->em->flush();

    // deleting a group cascades to its members and entries, changing
    // many users' permissions, so clear every user's cache
    $this->clearAllUserCache();

    return render_json(['deleted' => $groupId]);
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
