<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Entity\User;
use SimpleValidator as V;

class AdminUsers extends Instance_Controller {
  private const PER_PAGE_OPTIONS = [25, 50, 100];
  private const DEFAULT_PER_PAGE = 100;

  private EntityManager $em;

  public function __construct() {
    parent::__construct();
    $this->load->library('SimpleValidator');
    $this->em = $this->doctrine->em;
  }

  public function users() {
    $this->abortUnlessSuperAdmin();

    if ($this->input->server('REQUEST_METHOD') !== 'GET') {
      return abort_json(['error' => 'Method Not Allowed'], 405);
    }

    return $this->listUsers();
  }

  private function listUsers(): CI_Output {
    $params = $this->input->get() ?? [];

    try {
      $validated = V::validate($params, [
        'search' => [V::string()],
        'userType' => [V::regex('/\A(Local|Remote)\z/', 'Must be Local or Remote.')],
        'isSuperAdmin' => [V::regex('/\A(true|false)\z/', 'Must be true or false.')],
      ]);
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    $search = trim($validated['search'] ?? '');
    $search = $search === '' ? null : $search;
    $userType = $validated['userType'] ?? null;
    $isSuperAdmin = isset($validated['isSuperAdmin'])
      ? $validated['isSuperAdmin'] === 'true'
      : null;

    $page = filter_var(
      $params['page'] ?? null,
      FILTER_VALIDATE_INT,
      ['options' => ['min_range' => 1]]
    ) ?: 1;
    $requestedPerPage = filter_var($params['perPage'] ?? null, FILTER_VALIDATE_INT);
    $perPage = in_array($requestedPerPage, self::PER_PAGE_OPTIONS, true)
      ? $requestedPerPage
      : self::DEFAULT_PER_PAGE;

    $total = (int) $this->filteredUsersQuery($search, $userType, $isSuperAdmin)
      ->select('COUNT(u.id)')
      ->getQuery()
      ->getSingleScalarResult();

    $offset = ($page - 1) * $perPage;
    $users = $offset < $total
      ? $this->filteredUsersQuery($search, $userType, $isSuperAdmin)
        ->select('u', 'i')
        ->leftJoin('u.instance', 'i')
        ->orderBy('u.id', 'DESC')
        ->setFirstResult($offset)
        ->setMaxResults($perPage)
        ->getQuery()
        ->getResult()
      : [];

    return render_json([
      'users' => array_map(fn(User $user) => $this->toUserRow($user), $users),
      'page' => $page,
      'perPage' => $perPage,
      'total' => $total,
    ]);
  }

  private function filteredUsersQuery(
    ?string $search,
    ?string $userType,
    ?bool $isSuperAdmin
  ): QueryBuilder {
    $qb = $this->em->createQueryBuilder()->from(User::class, 'u');

    if ($search !== null) {
      $qb->andWhere($qb->expr()->orX(
        'LOWER(u.displayName) LIKE LOWER(:search)',
        'LOWER(u.username) LIKE LOWER(:search)',
        'LOWER(u.email) LIKE LOWER(:search)',
        'LOWER(u.emplid) LIKE LOWER(:search)'
      ))->setParameter('search', '%' . $this->escapeLikeWildcards($search) . '%');
    }

    if ($userType !== null) {
      $qb->andWhere('u.userType = :userType')->setParameter('userType', $userType);
    }

    if ($isSuperAdmin === true) {
      $qb->andWhere('u.isSuperAdmin = true');
    } elseif ($isSuperAdmin === false) {
      $qb->andWhere($qb->expr()->orX('u.isSuperAdmin = false', 'u.isSuperAdmin IS NULL'));
    }

    return $qb;
  }

  private function escapeLikeWildcards(string $text): string {
    return addcslashes($text, '%_\\');
  }

  private function toUserRow(User $user): array {
    $instance = $user->getInstance();

    return [
      'id' => $user->getId(),
      'username' => $user->getUsername(),
      'displayName' => $user->getDisplayName(),
      'email' => $user->getEmail(),
      'emplid' => $user->getEmplid(),
      'userType' => $user->getUserType(),
      'isSuperAdmin' => (bool) $user->getIsSuperAdmin(),
      'hasExpiry' => (bool) $user->getHasExpiry(),
      'expires' => $user->getExpires()?->format('c'),
      'createdAt' => $user->getCreatedAt()?->format('c'),
      'instance' => $instance
        ? ['id' => $instance->getId(), 'name' => $instance->getName()]
        : null,
    ];
  }
}
