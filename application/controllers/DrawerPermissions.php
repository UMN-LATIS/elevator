<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Entity\DrawerGroup;
use SimpleValidator as V;

/**
 * pure json api for the new drawer groups ui.
 *
 * Drawer groups are owned by a user, unlike instance groups which belong
 * to the instance. So every action is scoped to the signed-in user's own
 * groups and gated on "can create drawers", not instance admin.
 */
class DrawerPermissions extends Instance_Controller {
  private EntityManager $em;

  public function __construct() {
    parent::__construct();
    $this->load->library('SimpleValidator');
    $this->em = $this->doctrine->em;
  }

  /**
   * REST entry point for /drawerPermissions/groups[/{id}].
   *
   * Trailing URL segments arrive as method args, so a request to
   * /drawerPermissions/groups/5 calls this with $groupId = "5".
   */
  public function groups($groupId = null): CI_Output {
    $this->abortUnlessCanCreateDrawers();

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

    // a known route, but the verb isn't allowed on it
    return abort_json(['error' => 'Method Not Allowed'], 405);
  }

  private function listGroups(): CI_Output {
    $personalGroupId = $this->getPersonalDrawerGroup()?->getId();

    $groups = $this->em
      ->getRepository(DrawerGroup::class)
      ->findBy(['user' => $this->user_model->user]);

    $isNotPersonalGroup = fn(DrawerGroup $group): bool =>
    $group->getId() !== $personalGroupId;
    $visibleGroups = array_values(array_filter($groups, $isNotPersonalGroup));

    return render_json(['groups' => $visibleGroups]);
  }

  /**
   * GET /drawerPermissions/groups/{id}: one of the user's own groups.
   */
  private function showGroup(int $groupId): CI_Output {
    $this->abortIfPersonalGroup($groupId);

    $group = $this->findOwnGroup($groupId);
    if (!$group) {
      return abort_json(['error' => 'Group not found'], 404);
    }

    return render_json(['group' => $group]);
  }

  /**
   * POST /drawerPermissions/groups: create a "Specific People" group owned
   * by the signed-in user. Members and other group types arrive in later
   * slices, so the type is fixed to User here.
   */
  private function createGroup(): CI_Output {
    try {
      $validated = V::validate($this->requestBody(), $this->groupLabelRules());
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    $group = new DrawerGroup();
    $group->setUser($this->user_model->user);
    $group->setGroupType(USER_TYPE);
    $group->setGroupLabel($validated['label']);
    // User groups carry members as entries, so the scalar flag stays null
    $group->setGroupValue(null);

    $this->em->persist($group);
    $this->em->flush();

    $this->clearUserCache();

    return render_json(['group' => $group], 201);
  }

  /**
   * PUT|PATCH /drawerPermissions/groups/{id}: rename a group. Type changes
   * come with the type selector in a later slice.
   */
  private function updateGroup(int $groupId): CI_Output {
    $this->abortIfPersonalGroup($groupId);

    $group = $this->findOwnGroup($groupId);
    if (!$group) {
      return abort_json(['error' => 'Group not found'], 404);
    }

    try {
      $validated = V::validate($this->requestBody(), $this->groupLabelRules());
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    $group->setGroupLabel($validated['label']);
    $this->em->flush();

    $this->clearUserCache();

    return render_json(['group' => $group]);
  }

  /**
   * DELETE /drawerPermissions/groups/{id}: remove one of the user's groups.
   */
  private function deleteGroup(int $groupId): CI_Output {
    $this->abortIfPersonalGroup($groupId);

    $group = $this->findOwnGroup($groupId);
    if (!$group) {
      return abort_json(['error' => 'Group not found'], 404);
    }

    $this->em->remove($group);
    $this->em->flush();

    $this->clearUserCache();

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

    // we use a heuristic to find the personal drawer group.
    // since the personal drawer group is created automatically the first
    // time a user creates a drawer, we find the oldest user-owned group
    // with the current user as a member
    $query = $this->em
      ->getRepository(DrawerGroup::class)
      ->createQueryBuilder('drawerGroup')
      ->join('drawerGroup.group_values', 'entry')
      // 1. group owned by the current user
      ->where('drawerGroup.user = :ownUserId')
      // 2. is a `User` type group
      ->andWhere('drawerGroup.group_type = :groupType')
      // 3. includes the user's own id as a member entry
      ->andWhere('entry.groupValue = :ownUserId')
      ->setParameter('ownUserId', $ownUserId)
      ->setParameter('groupType', USER_TYPE)
      // The personal group is created the first time a user makes a drawer,
      // so get the oldest one
      ->orderBy('drawerGroup.id', 'ASC')
      ->setMaxResults(1)
      ->getQuery();

    return $query->getOneOrNullResult();
  }

  /**
   * Find a group by id only when the signed-in user owns it.
   */
  private function findOwnGroup(int $groupId): ?DrawerGroup {
    return $this->em
      ->getRepository(DrawerGroup::class)
      ->findOneBy([
        'id' => $groupId,
        'user' => $this->user_model->user,
      ]);
  }

  /**
   * Validation for a group's label. Rejects `< > "` to prevent HTML
   * injection while still allowing names like `R&D` and `Bob's Team`.
   */
  private function groupLabelRules(): array {
    return [
      'label' => [
        V::required(),
        V::maxLength(255),
        V::notRegex('/[<>"]/', 'Label cannot contain < > or " characters')
      ],
    ];
  }

  /**
   * Abort unless the signed-in user can create drawers. Mirrors the
   * capability Home exposes as userCanCreateDrawers: a super admin, an
   * instance grant of at least PERM_CREATEDRAWERS, or edit access on any
   * collection. Drawer groups are user-owned, so this is the gate rather
   * than instance admin.
   */
  private function abortUnlessCanCreateDrawers(): void {
    $this->abortUnlessAuthed();
    if (!$this->canCreateDrawers()) {
      abort_json(['error' => 'Forbidden'], 403);
    }
  }

  private function canCreateDrawers(): bool {
    if ($this->user_model->getIsSuperAdmin()) {
      return true;
    }
    if ($this->user_model->getAccessLevel('instance', $this->instance) >= PERM_CREATEDRAWERS) {
      return true;
    }
    // getMaxCollectionPermission returns null when the user has no
    // collection grants at all
    return ($this->user_model->getMaxCollectionPermission() ?? 0) >= PERM_CREATEDRAWERS;
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
