<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

use Doctrine\ORM\EntityManager;
use Entity\Collection;
use Entity\RecentCollection;
use SimpleValidator as V;

/**
 * pure json api for new ui
 *
 * Collection CRUD for the admin collections page. The legacy
 * CollectionManager keeps serving the old UI (plus share and bucket
 * creation) untouched, so the field assignment here deliberately
 * duplicates its save().
 */
class AdminCollections extends Instance_Controller {
  private EntityManager $em;

  public function __construct() {
    parent::__construct();
    $this->load->library('SimpleValidator');
    $this->em = $this->doctrine->em;
  }

  /**
   * REST entry point for /adminCollections/collections[/{id}].
   */
  public function collections($collectionId = null) {
    $this->abortUnlessAdmin();

    $method = $this->input->server('REQUEST_METHOD');

    if ($collectionId !== null) {
      $collectionId = filter_var($collectionId, FILTER_VALIDATE_INT);
      if ($collectionId === false) {
        return abort_json(['error' => 'Invalid ID'], 400);
      }
    }

    $route = $collectionId === null
      ? '/collections'
      : '/collections/{id}';

    $table = [
      '/collections' => [
        'GET' => fn() => $this->listCollections(),
        'POST' => fn() => $this->createCollection(),
      ],
      '/collections/{id}' => [
        'GET' => fn() => $this->getCollection($collectionId),
        'PUT' => fn() => $this->updateCollection($collectionId),
        'PATCH' => fn() => $this->updateCollection($collectionId),
        'DELETE' => fn() => $this->deleteCollection($collectionId),
      ],
    ];

    $handler = $table[$route][$method] ?? null;

    if ($handler) {
      return $handler();
    }

    return abort_json(['error' => 'Method Not Allowed'], 405);
  }

  /**
   * GET /adminCollections/collections
   */
  private function listCollections(): CI_Output {
    $collections = $this->instance->getCollections()->toArray();

    return render_json(['collections' => array_values($collections)]);
  }

  /**
   * GET /adminCollections/collections/{id}
   */
  private function getCollection(int $collectionId): CI_Output {
    $collection = $this->findCollectionInInstance($collectionId);
    if (!$collection) {
      return abort_json(['error' => 'Collection not found'], 404);
    }

    return render_json(['collection' => $this->toCollectionDetail($collection)]);
  }

  /**
   * POST /adminCollections/collections
   *
   * Omitted S3 fields fall back to the instance defaults and
   * showInBrowse defaults to true, matching the legacy new-collection
   * form's pre-filled values.
   */
  private function createCollection(): CI_Output {
    try {
      $validated = V::validate($this->requestBody(), $this->collectionSchema());
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    $parent = null;
    $parentId = (int) ($validated['parentId'] ?? 0);
    if ($parentId !== 0) {
      $parent = $this->findCollectionInInstance($parentId);
      if (!$parent) {
        return abort_json(['error' => 'Parent collection not found'], 422);
      }
    }

    $collection = new Collection();
    $collection->addInstance($this->instance);
    $collection->setTitle($validated['title']);
    $collection->setParent($parent);
    $collection->setShowInBrowse(
      filter_var($validated['showInBrowse'] ?? 'true', FILTER_VALIDATE_BOOLEAN)
    );
    $collection->setCollectionDescription($validated['description'] ?? null);
    $collection->setPreviewImage($validated['previewImageId'] ?? null);
    $collection->setBucket($validated['bucket'] ?? $this->instance->getDefaultBucket());
    $collection->setBucketRegion($validated['bucketRegion'] ?? $this->instance->getBucketRegion());
    $collection->setS3Key($validated['s3Key'] ?? $this->instance->getAmazonS3Key());
    $collection->setS3Secret($validated['s3Secret'] ?? $this->instance->getAmazonS3Secret());

    $this->em->persist($collection);
    $this->em->flush();

    return render_json(['collection' => $this->toCollectionDetail($collection)], 201);
  }

  /**
   * PUT|PATCH /adminCollections/collections/{id}
   *
   * Title is always required. The other fields change only when
   * present in the body, so both verbs behave like PATCH. A parentId
   * of 0 moves the collection to the top level.
   */
  private function updateCollection(int $collectionId): CI_Output {
    $collection = $this->findCollectionInInstance($collectionId);
    if (!$collection) {
      return abort_json(['error' => 'Collection not found'], 404);
    }

    try {
      $validated = V::validate($this->requestBody(), $this->collectionSchema());
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    if (array_key_exists('parentId', $validated)) {
      $parentId = (int) $validated['parentId'];
      if ($parentId === 0) {
        $collection->setParent(null);
      } else {
        $parent = $this->findCollectionInInstance($parentId);
        if (!$parent) {
          return abort_json(['error' => 'Parent collection not found'], 422);
        }
        if ($this->wouldCreateParentCycle($collection, $parent)) {
          return abort_json(
            ['error' => 'Parent cannot be the collection itself or one of its descendants'],
            422
          );
        }
        $collection->setParent($parent);
      }
    }

    $collection->setTitle($validated['title']);
    if (array_key_exists('showInBrowse', $validated)) {
      $collection->setShowInBrowse(
        filter_var($validated['showInBrowse'], FILTER_VALIDATE_BOOLEAN)
      );
    }
    if (array_key_exists('description', $validated)) {
      $collection->setCollectionDescription($validated['description']);
    }
    if (array_key_exists('previewImageId', $validated)) {
      $collection->setPreviewImage($validated['previewImageId']);
    }
    if (array_key_exists('bucket', $validated)) {
      $collection->setBucket($validated['bucket']);
    }
    if (array_key_exists('bucketRegion', $validated)) {
      $collection->setBucketRegion($validated['bucketRegion']);
    }
    if (array_key_exists('s3Key', $validated)) {
      $collection->setS3Key($validated['s3Key']);
    }
    if (array_key_exists('s3Secret', $validated)) {
      $collection->setS3Secret($validated['s3Secret']);
    }

    $this->em->flush();

    return render_json(['collection' => $this->toCollectionDetail($collection)]);
  }

  /**
   * DELETE /adminCollections/collections/{id}
   *
   * Legacy cascade parity: children move to the top level rather than
   * deleting with their parent, and RecentCollection rows are purged.
   * CollectionPermission grants cascade away at the ORM level.
   */
  private function deleteCollection(int $collectionId): CI_Output {
    $collection = $this->findCollectionInInstance($collectionId);
    if (!$collection) {
      return abort_json(['error' => 'Collection not found'], 404);
    }

    foreach ($collection->getChildren() as $child) {
      $child->setParent(null);
    }

    $recentCollections = $this->em
      ->getRepository(RecentCollection::class)
      ->findBy(['collection' => $collection]);
    foreach ($recentCollections as $recentCollection) {
      $this->em->remove($recentCollection);
    }

    $this->em->remove($collection);
    $this->em->flush();

    $this->clearUserCache();

    return render_json(['deleted' => $collectionId]);
  }

  /**
   * Shared body schema for create and update. Title is always
   * required, every other field is optional and update only touches
   * the ones present.
   */
  private function collectionSchema(): array {
    return [
      'title' => [V::required(), V::string()],
      'parentId' => [V::integer()],
      'showInBrowse' => [V::string()],
      'description' => [V::string()],
      'previewImageId' => [V::string()],
      'bucket' => [V::string()],
      'bucketRegion' => [V::string()],
      's3Key' => [V::string()],
      's3Secret' => [V::string()],
    ];
  }

  /**
   * The detail shape: the entity's list fields plus the admin-only
   * settings, including the S3 secret (parity with the legacy edit
   * form, which reveals it to instance admins).
   */
  private function toCollectionDetail(Collection $collection): array {
    return array_merge($collection->jsonSerialize(), [
      'description' => $collection->getCollectionDescription(),
      'bucket' => $collection->getBucket(),
      'bucketRegion' => $collection->getBucketRegion(),
      's3Key' => $collection->getS3Key(),
      's3Secret' => $collection->getS3Secret(),
    ]);
  }

  /**
   * Find the collection with `$collectionId` among this instance's own
   * collections.
   *
   * Collection ids are global, so fetching one straight from the
   * repository would let an admin reach another instance's collection.
   * Scanning the instance's collection list enforces membership, the
   * same ownership pattern as AdminPermissions.
   *
   * @return ?Entity\Collection null when the instance has no such
   *   collection
   */
  private function findCollectionInInstance(int $collectionId): ?Collection {
    foreach ($this->instance->getCollections() as $candidate) {
      if ($candidate->getId() === $collectionId) {
        return $candidate;
      }
    }
    return null;
  }

  /**
   * A collection's parent may not be itself or one of its descendants,
   * otherwise getFlattenedChildren() would recurse forever. Legacy only
   * disabled these options client-side, so the API enforces it here.
   *
   * The walk is iterative with a visited set because legacy never
   * guarded cycles, so pre-existing bad data must not hang the check.
   */
  private function wouldCreateParentCycle(Collection $collection, Collection $newParent): bool {
    if ($newParent->getId() === $collection->getId()) {
      return true;
    }

    $visitedIds = [];
    $toVisit = [$collection];
    while (!empty($toVisit)) {
      $current = array_pop($toVisit);
      foreach ($current->getChildren() as $child) {
        $childId = $child->getId();
        if (isset($visitedIds[$childId])) {
          continue;
        }
        $visitedIds[$childId] = true;
        if ($childId === $newParent->getId()) {
          return true;
        }
        $toVisit[] = $child;
      }
    }
    return false;
  }

  /**
   * Clear cached user permissions after a delete so the cascaded
   * grant removals take effect immediately.
   */
  private function clearUserCache(): void {
    if ($this->config->item('enableCaching')) {
      $this->userCache->clear();
    }
  }
}
