<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Aws\S3\Model\ClearBucket;

use Aws\Sts\StsClient;
use Aws\Exception\MultipartUploadException;

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

	public function putObject($sourceFile, $destKey, $storageClass = AWS_REDUCED, $targetMimeType = null) {
		if(!$targetMimeType) {
			$targetMimeType = mime_content_type($sourceFile);
		}
		

		if(filesize($sourceFile) < 100*1024*1024) {
			try {
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
		}
		else {

			$this->transferCount = 0;
			$beforeFunction = function(Aws\Command $command) {
				$this->transferCount++;
				if($this->transferCount % 10 == 0) {
					if(@$this->pheanstalk !== null && @$this->job !==null) {
						$this->pheanstalk->touch($this->job);	
						
					}
				}

			};

			$uploader = new \Aws\S3\MultipartUploader($this->s3Client, $sourceFile, [
				'bucket' => $this->bucket,
				'key'    => $destKey,
				'before_initiate' => function(\Aws\Command $command) use ($targetMimeType, $storageClass)  //  HERE IS THE RELEVANT CODE
				{
					$command['ContentType'] = $targetMimeType; 
					$command['StorageClass'] = $storageClass;  
				},
				"before_upload" => $beforeFunction
				]);

			do {
				try {
					$result = $uploader->upload();
				} catch (MultipartUploadException $e) {
					$uploader = new \Aws\S3\MultipartUploader($this->s3Client, $sourceFile, [
						'state' => $e->getState(),
						]);
				}
			} while (!isset($result));

			return $result ? true : false;
		}


		return true;
	}


	public function putDirectory($sourceDirectory, $destKey, $storageClass = AWS_REDUCED, $job=null) {
		try {
			$options["concurrency"] = 50;
			$this->storageClass = $storageClass;
			if(!$this->storageClass) {
				$this->storageClass = AWS_REDUCED;
			}
			
			$this->transferCount = 0;
			if($job) {
				$this->job = $job;
			}
			$beforeFunction = function(Aws\Command $command) {
				if (in_array($command->getName(), ['PutObject', 'CreateMultipartUpload'])) {
            		// Set custom cache-control metadata
            		// $this->logging->logError("storage class", $this->storageClass);
					$command['StorageClass'] = $this->storageClass;
				}

				$this->transferCount++;
				if($this->transferCount % 100 == 0) {
					if($this->pheanstalk !== null && $this->job !==null) {
						$this->pheanstalk->touch($this->job);	
					}
					
					echo ".";
				}

			};

			if($beforeFunction) {
				$options["before"] = $beforeFunction;
			}
			else {
				$options["before"] = function(Aws\Command $command) {

				};
			}
			$destinationPath = "s3://" . $this->bucket . "/" . $destKey;
			$manager = new \Aws\S3\Transfer($this->s3Client, $sourceDirectory, $destinationPath, $options);
			$manager->transfer();
		}
		catch (Aws\Exception\AwsException $e) {
			echo $sourceDirectory . "\n";
			echo $destKey . "\n";
			$this->logging->logError("uploadDirectory", $e);
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
				$originalName = preg_replace('/[^\x20-\x7E]/', '', $originalName);
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

	
	public function getSecurityTokenForPath($targetKey, $timeSeconds=3600) {
		try {
			
			$client = StsClient::factory(array(
			'region'=> 'us-east-1', // TODO should not be hardcoded
			'version'=> '2011-06-15',
			'credentials'=> ['key'    => $this->key,
			'secret' => $this->secret]));
			
			 $policy = [
			    'Statement' => array(
			      array(
			        'Sid' => 'SID' . time(),
			        'Action' => array(
			          's3:GetObject'
			        ),
			        'Effect' => 'Allow',
			        'Resource' => 'arn:aws:s3:::' . $this->bucket . "/" . $targetKey . "*"
			      )
			    )
			    ];

			$sessionToken = $client->getFederationToken(["Name"=>"policy_" . substr(md5($targetKey), 0, 15), "Policy"=>json_encode($policy), "DurationSeconds"=>$timeSeconds]);

			return $sessionToken['Credentials'];

		}
		catch (Exception $e) {
			echo $e;
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
		set_time_limit(200);
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
			$listObjectsParams = ['Bucket' => $this->bucket, 'Prefix' => $targetKey]; 
			$delete = Aws\S3\BatchDelete::fromListObjects($this->s3Client, $listObjectsParams); 
			$delete->delete();

		}
		else {
			if(!$this->sessionToken) {
				try {
					$client = StsClient::factory(array(
					'region'=> 'us-east-1', // TODO should not be hardcoded
					'version'=> '2011-06-15',
					'credentials'=> ['key'    => "KEY GOES HERE",
					'secret' => "SECRET GOES HERE"]));
					$this->sessionToken = $client->getSessionToken(["DurationSeconds"=>900, "SerialNumber"=>$serial, "TokenCode"=>$mfa]);
				}

				catch (Exception $e) {
					echo $targetKey;
					echo $this->bucket;
					echo "<hr>";
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
				echo $targetKey;
				echo $this->bucket;
				echo "<hr>";
				echo $e;
				$this->logging->logError("failed creating token", $e);
				return false;
			}


			try {
				if(!$this->bucket) {
					$this->logging->logError("missing bucket", "missing bucket for " . $targetKey . " " . $this->bucket);
					return false;
				}
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