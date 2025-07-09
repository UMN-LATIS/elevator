<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Aws\Exception\MultipartUploadException;
use Aws\S3\MultipartUploader;
use Aws\S3\ObjectUploader;
use Aws\S3\S3Client;

if (!function_exists('abort_json')) {
  // helper for making sure we stop execution
  // after rendering a JSON response
  function abort_json($json, $code = 400)
  {
    render_json($json, $code);
    exit; // Ensure no further output is sent
  }
}

class S3 extends Instance_Controller
{

  private $collection = null;
  private $s3Client = null;
  private $fileObjectId = null;
  private $contentType = null;

  public function __construct()
  {
    parent::__construct();
    $currentUser = $this->user_model->user;
    $this->checkAuthentication($currentUser);

    $collectionId = $this->input->post('collectionId');
    $this->collection = $this->checkCollectionAccess($collectionId);

    // client side should do a request to `assetManager/getFileContainer` to generate a file object ID
    // this will be used for naming the file in S3
    $fileObjectId  = $this->input->post('fileObjectId');
    $this->fileObjectId = $this->checkFileObjectId($fileObjectId);

    $contentType = $this->input->post('contentType');
    $this->contentType = $this->checkContentTypeParam($contentType);

    $this->s3Client = new S3Client([
      'version' => 'latest',
      'region' => $this->collection->getBucketRegion(),
      'credentials' => [
        'key' => $this->collection->getS3Key(),
        'secret' => $this->collection->getS3Secret(),
      ],
    ]);
  }


  // POST /s3/sign - Generate a signed URL for S3 upload (not multipart)
  public function sign()
  {
    $method = $this->input->server('REQUEST_METHOD');

    if ($method !== 'POST') {
      return render_json([
        'error' => 'Method not allowed',
      ], 405);
    }

    try {
      $command = $this->s3Client->getCommand('PutObject', [
        'Bucket' => $this->collection->getBucket(),
        'Key' => $this->getAWSFilePath(),
        'ContentType' => $this->contentType
      ]);

      $request = $this->s3Client->createPresignedRequest($command, '+1 hour');
      $signedUrl = (string) $request->getUri();

      return render_json([
        'url' => $signedUrl,
        'method' => 'PUT',
      ]);
    } catch (Exception $e) {
      return abort_json([
        'error' => 'Failed to generate signed URL: ' . $e->getMessage(),
      ], 500);
    }
  }

  public function multipart($uploadId = null, $partNumberOrComplete = null)
  {
    $method = $this->input->server('REQUEST_METHOD');

    if ($method !== 'POST') {
      return abort_json([
        'error' => 'Method not allowed',
      ], 405);
    }

    // POST /s3/multipart - Start a multipart upload
    if ($uploadId === null && $partNumberOrComplete === null) {
      return $this->startMultipartUpload();
    }

    // POST /s3/multipart/{uploadId}
    if ($uploadId !== null && $partNumberOrComplete === null) {
      return $this->getListOfParts($uploadId);
    }

    // POST /s3/multipart/{uploadId}/{partNumber}
    if ($uploadId !== null && is_numeric($partNumberOrComplete)) {
      return $this->getSignedUrlForPart($uploadId, $partNumberOrComplete);
    }

    // POST /s3/multipart/{uploadId}/complete
    if ($uploadId !== null && strtolower($partNumberOrComplete) === 'complete') {
      return $this->completeMultipartUpload($uploadId);
    }

    // If we reach here, it means the request is not valid for multipart operations
    return abort_json([
      'error' => 'Page not found',
    ], 404);
  }

  // POST /s3/multipart - Start a multipart upload
  private function startMultipartUpload()
  {
    $metadata = $this->checkMetaData();

    $command = $this->s3Client->getCommand('CreateMultipartUpload', [
      'Bucket' => $this->collection->getBucket(),
      'Key' => $this->getAWSFilePath(),
      'ContentType' => $this->contentType,
      'Metadata' => $metadata,
    ]);

    $result = $this->s3Client->execute($command);

    if (!$result || !isset($result['UploadId'])) {
      return render_json([
        'error' => 'Failed to start multipart upload',
      ], 500);
    }

    return render_json([
      "message" => "Multipart upload started successfully",
      "uploadId" => $result['UploadId'],
      "key" => $this->getAWSFilePath(),
    ]);
  }

  // GET /s3/mutipart/{uploadId}/{partNumber} - get a signed URL for a specific part of a multipart upload
  private function getSignedUrlForPart($uploadId, $partNumber)
  {
    $uploadId = $this->checkUploadId($uploadId);
    $partNumber = $this->checkPartNumber($partNumber);

    try {
      $command = $this->s3Client->getCommand('UploadPart', [
        'Bucket' => $this->collection->getBucket(),
        'Key' => $this->getAWSFilePath(),
        'UploadId' => $uploadId,
        'PartNumber' => $partNumber,
      ]);

      $result = $this->s3Client->createPresignedRequest($command, '+1 hour');

      return render_json([
        'method' => 'PUT',
        'url' => (string)$result->getUri()
      ]);
    } catch (MultipartUploadException $e) {
      return abort_json([
        'error' => 'Failed to get signed URL for part: ' . $e->getMessage(),
      ], 500);
    }
  }

  // GET /s3/multipart/{uploadId} - Get a list of uploaded parts and etags
  private function getListOfParts($uploadId)
  {
    $uploadId = $this->checkUploadId($uploadId);

    try {
      $command = $this->s3Client->getCommand('ListParts', [
        'Bucket' => $this->collection->getBucket(),
        'Key' => $this->getAWSFilePath(),
        'UploadId' => $uploadId,
      ]);

      $result = $this->s3Client->execute($command);

      return render_json([
        'parts' => $result['Parts'] ?? [],
      ]);
    } catch (MultipartUploadException $e) {
      return abort_json([
        'error' => 'Failed to list parts: ' . $e->getMessage(),
      ], 500);
    }
  }

  // POST /s3/mutlipart/{uploadId}/complete - Complete a multipart upload
  private function completeMultipartUpload($uploadId)
  {
    $parts = $this->checkUploadParts($this->input->post('parts'));

    try {
      $command = $this->s3Client->getCommand('CompleteMultipartUpload', [
        'Bucket' => $this->collection->getBucket(),
        'Key' => $this->getAWSFilePath(),
        'UploadId' => $uploadId,
        'MultipartUpload' => [
          'Parts' => $parts
        ]
      ]);

      $result = $this->s3Client->execute($command);

      return render_json([
        'message' => 'completeMultipartUpload',
        'Location' => $result['Location'] ?? null,
      ]);
    } catch (MultipartUploadException $e) {
      return abort_json([
        'error' => 'Failed to complete multipart upload: ' . $e->getMessage(),
      ], 500);
    }
  }

  private function checkAuthentication($user)
  {
    if (!$user) {
      return abort_json([
        'error' => 'You must be logged in to perform this action.'
      ], 401);
    }
  }

  private function checkFileObjectId(
    $fileObjectId = null
  ) {
    if (!$fileObjectId) {
      return abort_json([
        'error' => 'File Object ID parameter is required.'
      ], 400);
    }

    return $fileObjectId;
  }

  private function checkCollectionAccess($collectionId = null)
  {
    if (!$collectionId) {
      return abort_json([
        'error' => 'Collection ID parameter is required.'
      ], 400);
    }

    $collection = $this->collection_model->getCollection($collectionId);
    if (!$collection) {
      return abort_json([
        'error' => 'Collection not found: ' . $collectionId
      ], 404);
    }


    if (!$this->canUploadToCollection($collection)) {
      return abort_json([
        'error' => 'You do not have permission to upload to this collection.'
      ], 403);
    }

    return $collection;
  }

  private function canUploadToCollection($collection)
  {
    if (!$collection) {
      throw new InvalidArgumentException('Collection cannot be null');
    }

    if ($this->user_model->getIsSuperAdmin()) {
      return true;
    }

    $accessLevel = $this->user_model->getAccessLevel("collection", $collection);

    return $accessLevel >= PERM_ADDASSETS;
  }

  // aka the `key` in S3 terms
  private function getAWSFilePath()
  {
    if (!$this->fileObjectId) {
      throw new Exception('File Object ID Not Set');
    }

    return "original/{$this->fileObjectId}-source";
  }

  private function checkContentTypeParam($contentType = null)
  {
    if (!$contentType) {
      return abort_json([
        'error' => 'Missing contentType param.'
      ], 400);
    }

    if (!preg_match('/^[a-zA-Z0-9\/\-\+]+$/', $contentType)) {
      return abort_json([
        'error' => 'Invalid contentType param.'
      ], 400);
    }

    return $contentType;
  }

  private function checkMetadata($metadata = null)
  {
    if (!$metadata) {
      return [];
    }

    if (is_string($metadata)) {
      $metadata = json_decode($metadata, true);
    }

    if (!is_array($metadata)) {
      return abort_json(
        ['error' => 'metadata must be a valid JSON object.']
      );
    }

    return $metadata;
  }

  private function checkUploadId($uploadId = null)
  {
    if (!$uploadId || !preg_match('/^[a-zA-Z0-9\-_\.\+]+$/', $uploadId)) {
      return abort_json(['error' => 'Invalid uploadId parameter.']);
    }
    return $uploadId;
  }

  private function checkPartNumber($partNumber = null)
  {
    if (!is_numeric($partNumber) || $partNumber < 1 || $partNumber > 10000) {
      return abort_json(['error' => 'Invalid partNumber parameter.']);
    }
    return (int)$partNumber;
  }

  private function checkUploadParts($parts = null)
  {
    if (!$parts || !is_array($parts)) {
      return abort_json(['error' => 'Parts parameter is required and must be an array.']);
    }

    foreach ($parts as $part) {
      if (!isset($part['PartNumber']) || !isset($part['ETag'])) {
        return abort_json(['error' => 'Each part must have a PartNumber and ETag.']);
      }
      if (!is_numeric($part['PartNumber']) || !preg_match('/^[a-zA-Z0-9\-]+$/', $part['ETag'])) {
        return abort_json(['error' => 'Invalid PartNumber or ETag format.']);
      }
    }

    return $parts;
  }
}
