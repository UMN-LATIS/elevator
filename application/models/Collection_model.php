<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Aws\Sts\StsClient;


class collection_model extends CI_Model {

	private $collectionCache = array();
	private $s3collectionCache = array();
	public function __construct()
	{
		parent::__construct();

	}

	public function getCollection($collectionId) {

		if(!isset($this->collectionCache[$collectionId])) {
			$this->collectionCache[$collectionId] = $this->doctrine->em->find('Entity\Collection', $collectionId);
		}

		return $this->collectionCache[$collectionId];
	}

	public function getS3ClientForCollection($collectionId) {
		if(!isset($this->s3collectionCache[$collectionId])) {
			$collection = $this->getCollection($collectionId);

			$s3Client =  Aws\S3\S3Client::factory(array(
    		'credentials'=> ['key'    => $collection->getS3Key(),
    		'secret' =>  $collection->getS3Secret()
    		],
    		"scheme" => "http",
    		"region" => $collection->getBucketRegion(),
    		"version"=> "2006-03-01"
		));

			$this->s3collectionCache[$collectionId] = $s3Client;
		}

		return $this->s3collectionCache[$collectionId];
	}
}

/* End of file  */
/* Location: ./application/models/ */