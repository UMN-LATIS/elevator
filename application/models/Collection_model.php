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
		if(!is_numeric($collectionId)) {
			return false;
		}
		if(!isset($this->collectionCache[$collectionId])) {
			$this->collectionCache[$collectionId] = $this->doctrine->em->find('Entity\Collection', $collectionId);
		}

		if($this->collectionCache[$collectionId] == NULL) {
			return false;
		}

		return $this->collectionCache[$collectionId];
	}


	public function getFullHierarchy($collectionId) {

		$collection = $this->getCollection($collectionId);
		if(!$collection) {
			return [];
		}
		$output = [$collection];
		if($collection->getParent()) {
			$output = array_merge($output, $this->getFullHierarchy($collection->getParent()->getId()));
		}
		return $output;

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

	public function getUserCollections($user=null) {
		if(!$user && $this->user_model && $this->user_model) {
			$user = $this->user_model;
		}

		if(!$user) {
			return array();
		}

		$accessLevel = $user->getAccessLevel("instance",$this->instance);

		if($accessLevel < PERM_SEARCH) {
			$allowedCollections = $user->getAllowedCollections(PERM_SEARCH);
			if(count($allowedCollections) == 0) {
				return array();
			}
			$allParents = $this->instance->getCollectionsWithoutParent();

			
			$collections = array_values(array_uintersect($allParents, $allowedCollections, function($a, $b) { 
				if($a->getId() == $b->getId()) { 
					return 0;
				}
				if($a->getId() > $b->getId()) {
					return 1;
				}
				return -1;
			}));

			$collections = array_merge($collections, $this->findOrphans($allParents, $allowedCollections, $collections));



		}
		else {
			$collections = $this->instance->getCollectionsWithoutParent();
		}

		return $collections;

	}

	public function findOrphans($allParents, $allowedCollections, $foundCollections) {
		$orphans = [];

		foreach($allParents as $collection) {

			if(!in_array($collection, $allowedCollections)) {
				// see if this collection has any children we should allow
				$children = $collection->getFlattenedChildren();
				foreach($children as $child) {
					if(!in_array($child, $foundCollections) && in_array($child, $allowedCollections)) {
						$orphans[] = $child;
					}
				}	
			}
			
		}
		return $orphans;

	}


}

/* End of file  */
/* Location: ./application/models/ */