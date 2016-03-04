<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Aws\S3\Model\ClearBucket;

use Aws\Sts\StsClient;

class S3_model extends CI_Model {

	public $s3Client;
	private $secret;
	private $key;
	public $bucket;
	public $sessionToken = null;

	public function __construct($collection=null)
	{
		parent::__construct();
		if($collection) {
			$this->loadFromCollection($collection);
		}


	}

	/**
	 * For generating s3 urls (s3://)
	 * @return [type] [description]
	 */
	public function getSecretKeyPair() {
		return urlencode(urlencode($this->key)) . ":" . urlencode(urlencode($this->secret));
	}

	public function loadFromCollection($collection) {

		if(is_numeric($collection)) {
			$collection = $this->collection_model->getCollection($collection);
		}

		$this->secret = $collection->getS3Secret();
		$this->key = $collection->getS3Key();
		$this->bucket = $collection->getBucket();
		$this->s3Client = $this->collection_model->getS3ClientForCollection($collection->getId());

	}

	public function loadFromInstance($instance) {

		$this->secret = $instance->getAmazonS3Secret();
		$this->key = $instance->getAmazonS3Key();
		$this->bucket = $instance->getDefaultBucket();
		$this->s3Client = $this->s3_model->s3Client =  Aws\S3\S3Client::factory(array(
    					'credentials'=> ['key'    => $this->key,
    					'secret' =>  $this->secret],
    					"scheme" => "http",
    					"version"=>"2006-03-01",
    					"region" => $instance->getBucketRegion()
					));

	}

	public function putObject($sourceFile, $destKey, $storageClass = AWS_REDUCED) {
		try {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$targetMimeType = finfo_file($finfo, $sourceFile);
			$targetMimeType = mime_content_type($sourceFile);
			$this->s3Client->putObject(array(
				'Bucket' => $this->bucket,
	    		'Key'    => $destKey,
    			'Body'   => fopen($sourceFile, "rb"),
    			'StorageClass'   => $storageClass,
    			"ContentType" => $targetMimeType
    		));
		}
		catch (Exception $e) {
			$this->logging->logError("putObject", $e, $sourceFile);
			return false;
		}
		return true;
	}


	public function putDirectory($sourceDirectory, $destKey, $storageClass = AWS_REDUCED) {
		try {
			$this->s3Client->uploadDirectory($sourceDirectory, $this->bucket, $destKey);
		}
		catch (Exception $e) {
			$this->logging->logError("uploadDirectory", $e, $sourceFile);
			return false;
		}
		return true;


	}

	public function getObject($sourceKey, $destFile) {
		try {
		$result = $this->s3Client->getObject(array(
	    		'Bucket' => $this->bucket,
	    		'Key'    => $sourceKey,
	    		'SaveAs' => $destFile
			));
		}
		catch (Exception $e) {
			$this->logging->logError("getObject", $e, $sourceKey);
			return false;
		}

		return true;

	}

	public function objectInfo($targetKey) {
		try {
			$result = $this->s3Client->headObject([
	    		'Bucket' => $this->bucket,
	    		'Key'    => $targetKey
			]);
			return $result;
		}
		catch(Exception $e) {
			$this->logging->logError("objectInfo", $e, $targetKey);
			return false;
		}


	}

	public function getFilesAtKeyPath($targetKey) {
		try {

			$truncated = true;
			$nextMarker = null;
			$mergedContents  = array();
			while($truncated == true) {
				$result = $this->s3Client->listObjects([
	    			'Bucket' => $this->bucket,
	    			'Prefix'    => $targetKey,
	    			'Marker' => $nextMarker
				]);

				$mergedContents = array_merge($mergedContents, $result['Contents']);
				if($result['IsTruncated'] == true) {
					$lastElement = $result['Contents'][count($result['Contents'])-1];
					$nextMarker = $lastElement['Key'];
				}
				else {
					$truncated = false;
				}
			}

			$fileList = array();
			foreach($mergedContents as $entry) {
				$fileList[] = $entry['Key'];
			}
			return $fileList;
		}
		catch (Exception $e) {
			$this->logging->logError("getFilesAtKeyPath", $e, $targetKey);
			return array();
		}

	}


	public function getStorageClass($targetKey) {
		try {
			$result = $this->s3Client->listObjects([
	    		'Bucket' => $this->bucket,
	    		'Prefix'    => $targetKey
			]);
			if($result['Contents'][0]['StorageClass'] == "GLACIER") {
				$objectInfo = $this->objectInfo($targetKey);
				if(strlen($objectInfo['Restore'])>0) {
					$explodedInfo = explode(",", $objectInfo['Restore']);
					if($explodedInfo[0] == 'ongoing-request="false"') {
						return "RESTORED";
					}
				}
			}
			return $result['Contents'][0]['StorageClass'];
		}
		catch (Exception $e) {
			$this->logging->logError("getStorageClass", $e, $targetKey);
			return false;
		}
	}

	public function restoreObject($targetKey) {
		try {
			$result = $this->s3Client->restoreObject([
	    		'Bucket' => $this->bucket,
	    		'Key'    => $targetKey,
	    		'RestoreRequest' => [
	    			'Days' => 15
	    		]
			]);
		}
		catch (Exception $e) {
			$this->logging->logError("restoreObject", $e, $targetKey);
			return false;
		}
	}

	public function getObjectURL($targetKey) {
		return $this->s3Client->getObjectUrl($this->bucket, $targetKey);
	}

	public function getProtectedURL($targetKey, $originalName=null, $timeString="+240 minutes") {
		try {
			$options = array();

			$options["Bucket"] = $this->bucket;
			$options["Key"] = $targetKey;

			if($originalName) {
				$options["ResponseContentDisposition"] = 'attachment; filename="' . $originalName . '"';
			}
			else {
				// if we don't have an explicit filename, force S3 not to serve one.
				// we do this mostly because Flash doesn't accept files with a content disposition.
				$options["ResponseContentDisposition"] = '';
			}

			$cmd = $this->s3Client->getCommand('GetObject', $options);
			$request = $this->s3Client->createPresignedRequest($cmd, $timeString);

			return (string)$request->getUri();

		}
		catch (Exception $e) {
			error_log($e);
			$this->logging->logError("getProtectedURL", $e, $targetKey);
			return false;
		}

	}

	/**
	 * We have to copy the object to update the metadata
	 * this should probably preserve other metadta?
	 * @param [type] $targetKey   [description]
	 * @param [type] $contentType [description]
	 */
	public function setContentType($targetKey, $contentType) {



		$iterator = $this->s3Client->getIterator('ListObjects', array(
    		'Bucket' => $this->bucket,
		    'Prefix' => $targetKey
		));

		foreach($iterator as $object){
			$objectKey = $object['Key'];
			$metadata = $this->objectInfo($object['Key']);
			try {
				$result = $this->s3Client->copyObject([
				'Bucket' => $this->bucket,
	    		'Key'    => $objectKey,
				'StorageClass'   => $this->getStorageClass($targetKey),
				'ContentType' =>$contentType,
				'ContentDisposition'=>$metadata['ContentDisposition'],
				'MetadataDirective' => 'REPLACE',
				'CopySource'=>urlencode($this->bucket . "/" . $objectKey)
			]);
			}
			catch (Exception $e) {
				$this->logging->logError("setContentType", $e, $objectKey);
			}

		}
	}

	public function setStorageClass($targetKey, $targetClass) {
		$iterator = $this->s3Client->getIterator('ListObjects', array(
    		'Bucket' => $this->bucket,
		    'Prefix' => $targetKey
		));

		foreach($iterator as $object){
			$objectKey = $object['Key'];
			try {
				$result = $this->s3Client->copyObject([
				'Bucket' => $this->bucket,
	    		'Key'    => $objectKey,
				'StorageClass'   => $targetClass,
				'MetadataDirective' => 'COPY',
				'CopySource'=>urlencode($this->bucket . "/" . $objectKey)
			]);
			}
			catch (Exception $e) {
				$this->logging->logError("setStorageClass", $e, $objectKey);
			}
		}


	}

	public function deleteObject($targetKey, $serial=null, $mfa=null) {
		if(strlen($targetKey) <24) {
			$this->logging->logError("deleteObject", "Bad or invalid error key", $targetKey);
			return false;
		}

		if(!$serial && !$mfa && $targetKey) {
			// try to delete the normal way

			$this->s3Client = Aws\S3\S3Client::factory(array(
				'credentials'=>[
				'key'    => $this->key,
				'secret' => $this->secret],
				"version" => "2006-03-01",
	    		"region" => "us-east-1" // TODO: SHOULD NOT BE HARDCODED
	    		));

			$this->s3Client->deleteMatchingObjects($this->bucket, $targetKey);


		}
		else {
			if(!$this->sessionToken) {
				try {
					$client = StsClient::factory(array(
					'region'=> 'us-east-1', // TODO should not be hardcoded
					'version'=> '2011-06-15',
					'credentials'=> ['key'    => $this->key,
					'secret' => $this->secret]));
					$this->sessionToken = $client->getSessionToken(["DurationSeconds"=>900, "SerialNumber"=>$serial, "TokenCode"=>$mfa]);
				}
				catch (Exception $e) {
					echo $e;
					$this->logging->logError("failed creating token", $e);
					return false;
				}
			}
			try {
				$this->s3Client = Aws\S3\S3Client::factory(array(
					'credentials' => [
					'key'    => $this->sessionToken['Credentials']['AccessKeyId'],
					'secret' => $this->sessionToken['Credentials']['SecretAccessKey'],
					'token'  => $this->sessionToken['Credentials']['SessionToken']
					],
					"version" => "2006-03-01",
	    			"region" => "us-east-1" // TODO: SHOULD NOT BE HARDCODED
	    		));
			}
			catch (Exception $e) {
				$this->logging->logError("failed creating token", $e);
				return false;
			}


			try {
				$this->s3Client->deleteMatchingObjects($this->bucket, $targetKey);
			}
			catch (Exception $e) {
				echo $e;
				return false;
			}

		}

		return true;
	}

}

/* End of file  */
/* Location: ./application/models/ */