<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Aws\Exception\MultipartUploadException;
use Aws\S3\MultipartUploader;
use Aws\S3\ObjectUploader;
use Aws\S3\S3Client;

class S3 extends Instance_Controller
{

  private $collection = null;
  private $s3Client = null;
  private $fileObjectId = null;

  public function __construct()
  {
    parent::__construct();
    $this->checkAuthentication();
    $this->collection = $this->checkCollectionAccess();

    // client side should do a request to `assetManager/getFileContainer` to generate a file object ID
    // this will be used for naming the file in S3
    $this->fileObjectId = $this->checkFileObjectId();
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

    $contentType = $this->input->post('contentType');
    if (!$contentType) {
      return render_json([
        'error' => 'contentType parameter is required.'
      ], 400);
    }

    if (!preg_match('/^[a-zA-Z0-9\/\-\+]+$/', $contentType)) {
      return render_json([
        'error' => 'Invalid content type format.'
      ], 400);
    }

    try {
      $command = $this->s3Client->getCommand('PutObject', [
        'Bucket' => $this->collection->getBucket(),
        'Key' => $this->getAWSFilePath(),
        'ContentType' => $contentType
      ]);

      $request = $this->s3Client->createPresignedRequest($command, '+1 hour');
      $signedUrl = (string) $request->getUri();

      return render_json([
        'url' => $signedUrl,
        'method' => 'PUT',
      ]);
    } catch (Exception $e) {
      return render_json([
        'error' => 'Failed to generate signed URL: ' . $e->getMessage(),
      ], 500);
    }
  }

  public function multipart($uploadId = null, $partNumberOrComplete = null)
  {
    $method = $this->input->server('REQUEST_METHOD');

    if ($method !== 'POST') {
      return render_json([
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
    return render_json([
      'error' => 'Page not found',
    ], 404);
  }

  private function startMultipartUpload()
  {
    // Logic to start a multipart upload
    // This would typically involve creating a new upload ID and returning it
    return render_json([
      'message' => 'startMultipartUpload',
      'uploadId' => uniqid('upload_') // Example upload ID
    ]);
  }

  // GET /s3/mutipart/{uploadId}/{partNumber} - get a signed URL for a specific part of a multipart upload
  private function getSignedUrlForPart($uploadId, $partNumber)
  {
    return render_json([
      'message' => 'getSignedUrlForPart',
      'uploadId' => $uploadId,
      'partNumber' => $partNumber
    ]);
  }

  // GET /s3/multipart/{uploadId} - Get a list of uploaded parts and etags
  private function getListOfParts($uploadId)
  {
    return render_json([
      'message' => 'getListOfParts',
      'uploadId' => $uploadId
    ]);
  }

  // POST /s3/mutlipart/{uploadId}/complete - Complete a multipart upload
  private function completeMultipartUpload($uploadId)
  {
    return render_json([
      'message' => 'completeMultipartUpload',
      'uploadId' => $uploadId
    ]);
  }

  private function checkAuthentication()
  {
    if (!$this->user_model->user) {
      return render_json([
        'error' => 'You must be logged in to perform this action.'
      ], 401);
      exit;
    }
  }

  private function checkFileObjectId()
  {
    $fileObjectId = $this->input->post('fileObjectId');
    if (!$fileObjectId) {
      return render_json([
        'error' => 'File Object ID parameter is required.'
      ], 400);
      exit;
    }

    return $fileObjectId;
  }

  private function checkCollectionAccess()
  {
    $collectionId = $this->input->post('collectionId');
    if (!$collectionId) {
      return render_json([
        'error' => 'Collection ID parameter is required.'
      ], 400);
      exit;
    }

    $collection = $this->collection_model->getCollection($collectionId);
    if (!$collection) {
      return render_json([
        'error' => 'Collection not found: ' . $collectionId
      ], 404);
    }


    if (!$this->canUploadToCollection($collection)) {
      render_json([
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
}
