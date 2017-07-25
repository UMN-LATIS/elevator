<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_model extends CI_Model {

	public $collectionPermissions = array();
	public $instancePermissions = array();
	public $drawerPermissions = array();
	public $assetPermissions = array();
	public $recentDrawers = null;
	public $recentCollections = null;
	public $recentSearches = null;
	public $userLoaded = false;
	public $user = null;
	public $userId = null;
	public $assetOverride = false;
	private $maxRecents = 5;
	public $userData = array();


	public function __construct()
	{
		parent::__construct();


	}

	public function getDisplayNameForUserId($userId) {
			$user = $this->doctrine->em->find('Entity\User', $userId);
			if($user) {
				return $user->getDisplayName();	
			}
			else {
				return $userId;
			}
			
	}

	public function getUsernameForUserId($userId) {
			$user = $this->doctrine->em->find('Entity\User', $userId);
			if($user) {
				return $user->getUsername();	
			}
			else {
				return $userId;
			}
			
	}

	// convenience function, so that we know if they have edit access to any collection,
	//  used to drive the display of various UI elements
	public function getMaxCollectionPermission() {
		$maxPermission = 0;
		foreach($this->collectionPermissions as $permission) {
			$maxPermission = max($permission, $maxPermission);
		}
		return $maxPermission;
	}


	/**
	 * use permissions to get the max access level for specific object
	 */
	public function getAccessLevel($type, $object, $includeExcerpts=false) {


		if(isset($this->user) && $this->getIsSuperAdmin()) {
			return 70;
		}

		switch($type) {
			case "collection":
				if(array_key_exists($object->getId(),$this->collectionPermissions)) {
					return $this->collectionPermissions[$object->getId()];
				}
				break;
			case "instance":
				if(array_key_exists($object->getId(),$this->instancePermissions)) {
					return $this->instancePermissions[$object->getId()];
				}
				break;
			case "drawer":
				if(array_key_exists($object->getId(),$this->drawerPermissions)) {
					return $this->drawerPermissions[$object->getId()];
				}
				break;
			case "asset":
				$assetDrawers = $object->getDrawers($includeExcerpts);
				$perm = 0;
				foreach($assetDrawers as $drawer) {
					if(array_key_exists($drawer, $this->drawerPermissions)) {
						$perm = max($this->drawerPermissions[$drawer], $perm);
					}
				}

				$assetCollection = (int)$object->getGlobalValue("collectionId");
				if(array_key_exists($assetCollection, $this->collectionPermissions)) {
					$perm = max($perm, $this->collectionPermissions[$assetCollection]);
				}

				$collection = $this->collection_model->getCollection($assetCollection);
				foreach($collection->getInstances() as $instance) {
					if(array_key_exists($instance->getId(), $this->instancePermissions)) {
						$perm = max($perm, $this->instancePermissions[$instance->getId()]);
					}
				}
				if(array_key_exists($object->getObjectId(),$this->assetPermissions)) {
					$perm = max($perm, $this->assetPermissions[$object->getObjectId()]);
				}

				return $perm;
				break;
			default:
				return 0;
		}

		return 0;

	}


	/**
	 * loads user from DB, sets up a user with normalized perms
	 * saves all this stuff in a session
	 * @param  [type] $username [description]
	 * @param  [type] $type     [description]
	 * @return [type]           [description]
	 */
	public function loadUser($userId=null) {

		if($userId) {
			$this->userId = $userId;
			$this->user = $this->doctrine->em->find('Entity\User', $userId);
			if($this->user === null) {
				$this->userLoaded = false;
				// TOOD: if this is a real user create oned
				return;

			}

			
			if($this->user->getUserType() == "Remote") {
				$authHelper = $this->getAuthHelper();
				$this->userData = $authHelper->populateUserData($this->user);	
			}
			

			
			$this->userLoaded = true;
			$this->resolvePermissions();
		}


	}

	function getPermissions($entityType, $groupType, $groupValue, $limit=null) {
		$result = $this->doctrine->em->getRepository("Entity\\" . $entityType)->createQueryBuilder('i')

   			->join("i.group_values", "r", "with", "i.group_type = :group_type")
   			->where(":group_value_number LIKE r.groupValue")
   			->setParameter('group_type', $groupType)
   			->setParameter('group_value_number', $groupValue);

		if($limit != null && $entityType == "DrawerGroup") {
			$result = $result->andWhere("i.user = :user")
				->setParameter("user", $limit);
		}

		$result = $result->getQuery();
   		return $result->getResult();
	}

	public function resolvePermissions() {
		// a place to store the all of the groups that the user is in
		$instance_groups = array();

		// get all groups that user is in
		// groups as single user


		$instance_groups = array_merge($instance_groups, $this->doctrine->em->getRepository("Entity\InstanceGroup")->findBy(['group_type' => 'All']));

		if($this->userLoaded) {
			$instance_groups = array_merge($instance_groups, $this->doctrine->em->getRepository("Entity\InstanceGroup")->findBy(['group_type' => 'Authed', 'group_value' => 1]));
		}
		if($this->user && $this->user->getUserType() == "Remote") {
			$instance_groups = array_merge($instance_groups, $this->doctrine->em->getRepository("Entity\InstanceGroup")->findBy(['group_type' => 'Authed_remote', 'group_value' => 1]));
		}
		if($this->user) {
			$instance_groups = array_merge($instance_groups, $this->getPermissions("InstanceGroup", USER_TYPE, $this->user->getId()));

		}

		$authHelper = $this->getAuthHelper();
		
		$groupLookups = $authHelper->getGroupMapping($this->userData);
		foreach($groupLookups as $type=>$values) {
			foreach($values as $value) {
				$instance_groups = array_merge($instance_groups, $this->getPermissions("InstanceGroup", $type, $value));	
			}
		}
		foreach ($instance_groups as $instance_group) {
			foreach ($instance_group->getInstancePermissions() as $instancePermission) {
				if (array_key_exists($instancePermission->getInstance()->getId(), $this->instancePermissions)) {
					$this->instancePermissions[$instancePermission->getInstance()->getId()] = max($this->instancePermissions[$instancePermission->getInstance()->getId()], $instancePermission->getPermission()->getLevel());
				}
				else {
					$this->instancePermissions[$instancePermission->getInstance()->getId()] = $instancePermission->getPermission()->getLevel();
				}
			}

			foreach ($instance_group->getCollectionPermissions() as $collectionPermission) {
				$collectionArray = array_merge([$collectionPermission->getCollection()], $collectionPermission->getCollection()->getFlattenedChildren());
				foreach($collectionArray as $collection) {
					if (array_key_exists($collection->getId(), $this->collectionPermissions)) {
						$this->collectionPermissions[$collection->getId()] = max($this->collectionPermissions[$collection->getId()], $collectionPermission->getPermission()->getLevel());
					}
					else {
						$this->collectionPermissions[$collection->getId()] = $collectionPermission->getPermission()->getLevel();
					}
				}

			}
		}


		// a place to store the all of the groups that the user is in
		$drawer_groups = array();

		$drawer_groups = array_merge($drawer_groups, $this->doctrine->em->getRepository("Entity\DrawerGroup")->findBy(['group_type' => 'All']));
		if($this->userLoaded) {
			$drawer_groups = array_merge($drawer_groups, $this->doctrine->em->getRepository("Entity\DrawerGroup")->findBy(['group_type' => 'Authed', 'group_value' => 1]));
		}

		if($this->user && $this->user->getUserType() == "Remote") {
			$drawer_groups = array_merge($drawer_groups, $this->doctrine->em->getRepository("Entity\DrawerGroup")->findBy(['group_type' => 'Authed_remote', 'group_value' => 1]));
		}

		if($this->user) {
			$drawer_groups = array_merge($drawer_groups,$this->getPermissions("DrawerGroup", USER_TYPE, $this->user->getId()));
		}


		$groupLookups = $authHelper->getGroupMapping($this->userData);

		foreach($groupLookups as $type=>$values) {
			foreach($values as $value) {
				$drawer_groups = array_merge($drawer_groups,$this->getPermissions("DrawerGroup", $type,$value));
			}
		}

		foreach ($drawer_groups as $drawer_group) {
			foreach ($drawer_group->getPermissions() as $drawerPermission) {
				if (array_key_exists($drawerPermission->getDrawer()->getId(), $this->drawerPermissions)) {
					$this->drawerPermissions[$drawerPermission->getDrawer()->getId()] = max($this->drawerPermissions[$drawerPermission->getDrawer()->getId()], $drawerPermission->getPermission()->getLevel());
				}
				else {
					$this->drawerPermissions[$drawerPermission->getDrawer()->getId()] = $drawerPermission->getPermission()->getLevel();
				}
			}
		}
	}

	public function isInstanceAdmin() {
		if(!is_null($this->instance) && isset($this->instancePermissions[$this->instance->getId()])) {
			return ($this->instancePermissions[$this->instance->getId()]>=60)?true:false;
		}
		return false;
	}

	/**
	 * FIFO capped
	 * TODO: store to a recentDrawers table?
	 * @param [drawer entity] $drawer [description]
	 */
	public function addRecentDrawer($drawer) {

		if(!$this->user) {
			return;
		}


		if(!is_array($this->recentDrawers)) {
			$this->getRecentDrawers();
		}

		foreach($this->recentDrawers as $recent) {
			if($drawer->getId() == $recent->getDrawer()->getId()) {
				return;
			}
		}

		if(count($this->recentDrawers)>=$this->maxRecents) {
			$oldDrawer= reset($this->recentDrawers);
			$this->user->removeRecentDrawer($oldDrawer);
		}

		$recentDrawer = new Entity\RecentDrawer();

		$recentDrawer->setDrawer($drawer);
		$recentDrawer->setUser($this->user);
		$recentDrawer->setCreatedAt(new DateTime());
		$recentDrawer->setInstance($this->instance);
		$this->user->addRecentDrawer($recentDrawer);

		$this->doctrine->em->persist($this->user);

		$this->doctrine->em->flush();

		if($this->config->item('enableCaching')) {
			$this->doctrineCache->setNamespace('userCache_');
			$this->doctrineCache->delete($this->userId);
		}

		$this->recentDrawers = null;
		$this->getRecentDrawers();

	}


	/**
	 * [addRecentCollection description]
	 * TODO: store to a recentCollections table
	 * @param [collection entity] $collection [description]
	 */
	public function addRecentCollection($collection) {
		if(!is_array($this->recentCollections)) {
			$this->getRecentCollections();
		}

		foreach($this->recentCollections as $recent) {
			if($collection->getId() == $recent->getCollection()->getId()) {
				return;
			}
		}

		if(!$this->user) {
			return;
		}

		if(count($this->recentCollections)>=$this->maxRecents) {
			$oldCollection= reset($this->recentCollections);
			$this->user->removeRecentCollection($oldCollection);
		}

		$recentCollection = new Entity\RecentCollection();

		$recentCollection->setCollection($collection);
		$recentCollection->setUser($this->user);
		$recentCollection->setCreatedAt(new DateTime());
		$recentCollection->setInstance($this->instance);
		$this->user->addRecentCollection($recentCollection);

		$this->doctrine->em->persist($this->user);

		$this->doctrine->em->flush();

		if($this->config->item('enableCaching')) {
			$this->doctrineCache->setNamespace('userCache_');
			$this->doctrineCache->delete($this->userId);
		}

		$this->recentCollections= null;
		$this->getRecentCollections();
	}


	public function getDrawers($adminOnly=false)
	{
		$targetArray = array();
		foreach($this->drawerPermissions as $drawerId=>$perm) {
			if(!$adminOnly && $perm>PERM_NOPERM) {
				$targetArray[] = $drawerId;
			}
			elseif($adminOnly && $perm>=PERM_CREATEDRAWERS) {
				$targetArray[] = $drawerId;
			}
		}

		if(count($targetArray)>0) {
			$drawerList = $this->doctrine->em->getRepository("Entity\Drawer")->findBy(['id' => $targetArray, 'instance'=>$this->instance]);
			return $drawerList;
		}
		else {
			return array();
		}

	}

	public function getAllowedCollections($permLevel=false) {
		if(!$permLevel) {
			return array();
		}
		$instanceCollections = $this->instance->getCollections()->toArray();
		$allowedCollections = array();
		if($this->user_model && $this->user_model->getAccessLevel("instance",$this->instance) < $permLevel) {
			foreach($instanceCollections as $collection) {
				if($this->user_model->getAccessLevel("collection", $collection) >= $permLevel) {
					$allowedCollections[] = $collection;
					foreach($collection->getFlattenedChildren() as $child) {
						$allowedCollections[] = $child;
					}
				}
			}
		}
		else if($this->user_model->getAccessLevel("instance",$this->instance) >= $permLevel) {
			$allowedCollections = $instanceCollections;
		}




		return $allowedCollections;

	}

	/**
	 * lazy load the drawers
	 * this way we aren't loading them for page loads that don't have UI
	 * @return [type] [description]
	 */
	public function getRecentDrawers()
	{
		if($this->recentDrawers == null && $this->user) {

			$recentDrawers = $this->user->getRecentDrawers();
			$this->recentDrawers = array();
			if($recentDrawers->count()  > 0) {
				foreach($recentDrawers as $drawer) {
					if($drawer->getInstance() == $this->instance) {
						$this->recentDrawers[] = $drawer;
					}
				}
			}
		}
		return $this->recentDrawers;
	}

	/**
	 * lazy load the drawers
	 * this way we aren't loading them for page loads that don't have UI
	 * @return [type] [description]
	 */
	public function getRecentSearches()
	{
		if($this->recentSearches == null && $this->user) {

			$recentSearches = $this->doctrine->em->getRepository("Entity\SearchEntry")->findBy(["instance"=>$this->instance, "user"=>$this->user_model->user, "userInitiated"=>true], ["createdAt"=>"desc"], 5,0);

			$this->recentSearches = array();
			if(count($recentSearches) > 0) {
				$this->recentSearches = $recentSearches;
			}

		}
		return $this->recentSearches;
	}


	/**
	 * lazy load the collections
	 * @return [type] [description]
	 */
	public function getRecentCollections()
	{
		if($this->recentCollections == null) {
			if(!$this->user) {
				$this->recentCollections = array();
			}
			else {
				$recentCollections = $this->user->getRecentCollections();
				$this->recentCollections = array();

				if(count($recentCollections) > 0) {
					foreach($recentCollections as $collection) {
						if($collection->getInstance() == $this->instance) {
							$this->recentCollections[] = $collection;
						}
					}
				}
			}

		}
		return $this->recentCollections;
	}

	public function getAuthHelper() {
		$this->load->library($this->config->item("authHelper"));
		$authHelperName = $this->config->item("authHelper");
		$authHelper = new $authHelperName();
		return $authHelper;
	}


	public function generateKeys() {

		$apiKey = new Entity\ApiKey;

		$apiKey->setLabel("Generated Key");
		$apiKey->setApiKey(sha1($this->userId . "secretHash"));
		$apiKey->setApiSecret(sha1($this->userId . "secretKey"));
		$apiKey->setOwner($this->user);
		$apiKey->setRead(true);
		$this->doctrine->em->persist($apiKey);

		$this->doctrine->em->flush();
		return $apiKey;


	}


	/**
	 * Catch invalid method calls and see if our Doctrine child instance
	 * will respond - if so, call through to that.  Otherwise, flag an error.
	 * @return calls through to Doctrine instance method
	 */
	public function __call($method, $args) {
		if (is_object($this->user)) {
			if(method_exists($this->user, $method)) {
				return call_user_func(array($this->user,$method), $args);
			}
		}
		$this->logging->logError("backtrace", debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));

		trigger_error("Error Calling object method '$method' " . implode(', ', $args), E_USER_ERROR);
	}

	public function __sleep() {
		return ["collectionPermissions","instancePermissions","drawerPermissions","recentDrawers","recentSearches", "recentCollections","userLoaded","userId","userData"];

	}

	public function __wakeup() {
		if(!$this->userId) {
			return;
		}
		$this->user = $this->doctrine->em->find('Entity\User',$this->userId);

	}

}

/* End of file  */
/* Location: ./application/models/ */
