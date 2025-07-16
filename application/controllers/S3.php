<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Aws\Exception\MultipartUploadException;
use Aws\S3\S3Client;
use SimpleValidator as V;

class S3 extends Instance_Controller
{
  protected $fileObjectId;
  protected $contentType;
  protected $s3Client;
  protected $collectionId;
  protected $collection;

  public function __construct()
  {
    parent::__construct();
    $this->load->library('SimpleValidator');

    if (!$this->isUserAuthenticated()) {
      return abort_json(['error' => 'Unauthorized'], 401);
    }

    if ($this->input->method() !== 'post') {
      return abort_json(['error' => 'Method not allowed'], 405);
    }

    // setup instance variables common to all methods
    $data = $this->input->post();

    try {
      $validated = V::validate($data, [
        'collectionId' => [V::required(), V::integer()],
        'fileObjectId' => [V::required(), V::regex('/^[a-zA-Z0-9]+$/')],
        'contentType' => [V::required(), V::regex('/^[a-z]+\/[a-z0-9\-\+\.]+$/i')]
      ]);

      $this->fileObjectId = $validated['fileObjectId'];
      $this->contentType = $validated['contentType'];
      $this->collectionId = $validated['collectionId'];
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 400);
    }

    // get collection
    $this->collection = $this->collection_model->getCollection($this->collectionId);

    if (!$this->collection) {
      return abort_json(['error' => 'Collection not found'], 404);
    }

    // verify user permissions for this collection
    if (!$this->canUploadToCollection($this->collection)) {
      return abort_json(['error' => 'You do not have permission to upload to this collection.'], 403);
    }

    // create S3 client
    $this->s3Client =  new S3Client([
      'version' => 'latest',
      'region' => $this->collection->getBucketRegion(),
      'credentials' => [
        'key' => $this->collection->getS3Key(),
        'secret' => $this->collection->getS3Secret(),
      ],
    ]);

    if (!$this->s3Client) {
      return abort_json(['error' => 'Failed to create S3 client'], 500);
    }
  }

  public function sign()
  {
    try {
      $command = $this->s3Client->getCommand('PutObject', [
        'Bucket' => $this->collection->getBucket(),
        'Key' => $this->buildAWSFilePath($this->fileObjectId),
        'ContentType' => $this->contentType,
      ]);

      $request = $this->s3Client->createPresignedRequest($command, '+1 hour');

      return render_json([
        'url' => (string)$request->getUri(),
        'method' => 'PUT',
      ]);
    } catch (Exception $e) {
      return abort_json(['error' => 'Failed to generate signed URL: ' . $e->getMessage()], 500);
    }
  }

  public function multipart($uploadId = null, $action = null)
  {
    $schema = [
      'uploadId' => [V::regex('/^[a-zA-Z0-9\-_\.\+]+$/')],
      'action' => [V::regex('/^(complete|\d+)$/')]
    ];

    try {
      $validated = V::validate([
        'uploadId' => $uploadId,
        'action' => $action
      ], $schema);
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 400);
    }

    $uploadId = $validated['uploadId'] ?? null;
    $action = $validated['action'] ?? null;
    switch (true) {
      case $uploadId === null:
        // POST /s3/multipart
        // start a new multipart upload
        return $this->startMultipartUpload();

      case $action === null:
        // POST /s3/multipart/{uploadId}
        // list parts of an existing multipart upload
        return $this->getListOfParts($uploadId);

        // POST /s3/multipart/{uploadId}/{partNumber}
        // get signed url for a part
      case ctype_digit($action):
        return $this->signPart($uploadId, (int) $action);

        // POST /s3/multipart/{uploadId}/complete
        // complete the multipart upload
      case strtolower($action) === 'complete':
        return $this->completeMultipartUpload($uploadId);

      case strtolower($action) === 'abort':
        // POST /s3/multipart/{uploadId}/abort
        // abort the multipart upload
        return $this->abortMultipartUpload($uploadId);
      default:
        return abort_json(['error' => 'Page not found'], 404);
    }
  }

  private function startMultipartUpload()
  {
    try {
      // validate metadata
      $schema = [
        'metadata' => [V::array()]
      ];

      $validated = V::validate($this->input->post(), $schema);
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 400);
    }

    // start multipart upload
    try {
      $command = $this->s3Client->getCommand('CreateMultipartUpload', [
        'Bucket' => $this->collection->getBucket(),
        'Key' => $this->buildAWSFilePath($this->fileObjectId),
        'ContentType' => $this->contentType,
        'Metadata' => $validated['metadata'] ?? [],
      ]);

      $result = $this->s3Client->execute($command);

      return render_json([
        "message" => "Multipart upload started successfully",
        "uploadId" => $result['UploadId'],
        "key" => $this->buildAWSFilePath($this->fileObjectId),
      ]);
    } catch (Exception $e) {
      return abort_json(['error' => 'Failed to start multipart upload: ' . $e->getMessage()], 500);
    }
  }

  private function signPart($uploadId, $partNumber)
  {
    try {
      $schema = [
        'uploadId' => [V::required(), V::regex('/^[a-zA-Z0-9\-_\.\+]+$/')],
        'partNumber' => [V::required(), V::integer(), V::min(1), V::max(10000)]
      ];

      $validated = V::validate([
        'uploadId' => $uploadId,
        'partNumber' => $partNumber
      ], $schema);
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 422);
    }

    try {
      $command = $this->s3Client->getCommand('UploadPart', [
        'Bucket' => $this->collection->getBucket(),
        'Key' => $this->buildAWSFilePath($this->fileObjectId),
        'UploadId' => $validated['uploadId'],
        'PartNumber' => $validated['partNumber'],
      ]);

      $result = $this->s3Client->createPresignedRequest($command, '+1 hour');

      return render_json([
        'message' => 'signPart',
        'url' => (string)$result->getUri(),
        'method' => 'PUT',
        'partNumber' => $validated['partNumber'],
        'uploadId' => $validated['uploadId'],
      ]);
    } catch (Exception $e) {
      return abort_json(['error' => 'Failed to sign part: ' . $e->getMessage()], 500);
    }
  }

  private function getListOfParts($uploadId)
  {
    try {
      $schema = [
        'uploadId' => [V::required(), V::regex('/^[a-zA-Z0-9\-_\.\+]+$/')]
      ];

      $validated = V::validate(['uploadId' => $uploadId], $schema);
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 400);
    }

    try {
      $parts = $this->getUploadParts($validated['uploadId']);
      return render_json(['parts' => $parts]);
    } catch (MultipartUploadException $e) {
      return abort_json(['error' => 'Failed to list parts: ' . $e->getMessage()], 500);
    }
  }

  private function completeMultipartUpload($uploadId)
  {
    try {
      $schema = [
        'uploadId' => [V::required(), V::regex('/^[a-zA-Z0-9\-_\.\+]+$/')]
      ];

      $validated = V::validate(['uploadId' => $uploadId], $schema);
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 400);
    }

    $parts = $this->getUploadParts(
      $validated['uploadId']
    );

    try {
      $command = $this->s3Client->getCommand('CompleteMultipartUpload', [
        'Bucket' => $this->collection->getBucket(),
        'Key' => $this->buildAWSFilePath($this->fileObjectId),
        'UploadId' => $validated['uploadId'],
        'MultipartUpload' => ['Parts' => $parts]
      ]);

      $result = $this->s3Client->execute($command);

      return render_json([
        'message' => 'completeMultipartUpload',
        'location' => $result['Location'] ?? null,
      ]);
    } catch (MultipartUploadException $e) {
      return abort_json(['error' => 'Failed to complete multipart upload: ' . $e->getMessage()], 500);
    }
  }

  private function abortMultipartUpload($uploadId)
  {
    try {
      $schema = [
        'uploadId' => [V::required(), V::regex('/^[a-zA-Z0-9\-_\.\+]+$/')]
      ];

      $validated = V::validate(['uploadId' => $uploadId], $schema);
    } catch (ValidationException $e) {
      return abort_json(['errors' => $e->getErrors()], 400);
    }

    try {
      $command = $this->s3Client->getCommand('AbortMultipartUpload', [
        'Bucket' => $this->collection->getBucket(),
        'Key' => $this->buildAWSFilePath($this->fileObjectId),
        'UploadId' => $validated['uploadId'],
      ]);

      $this->s3Client->execute($command);

      return render_json(['message' => 'Multipart upload aborted successfully']);
    } catch (MultipartUploadException $e) {
      return abort_json(['error' => 'Failed to abort multipart upload: ' . $e->getMessage()], 500);
    }
  }

  private function isUserAuthenticated()
  {
    return !!$this->user_model?->user;
  }



  private function buildAWSFilePath($fileObjectId)
  {
    // the REVERSED fileObjectId is used to ensure that files are evenly distributed across S3 buckets
    $reversedId = strrev($fileObjectId);
    return "original/{$reversedId}-source";
  }


  private function getUploadParts($uploadId)
  {
    $command = $this->s3Client->getCommand('ListParts', [
      'Bucket' => $this->collection->getBucket(),
      'Key' => $this->buildAWSFilePath($this->fileObjectId),
      'UploadId' => $uploadId,
    ]);

    $result = $this->s3Client->execute($command);
    return $result['Parts'] ?? [];
  }


  private function canUploadToCollection($collection)
  {
    if (!$collection) {
      return false;
    }

    if ($this->user_model->getIsSuperAdmin()) {
      return true;
    }

    return $this->user_model->havePermForCollection(PERM_ADDASSETS, $collection->getId());
  }
}
