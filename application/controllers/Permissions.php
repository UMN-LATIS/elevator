<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Permissions extends Instance_Controller {


	public function edit($permissionType, $id = null)
	{


		$data['permissionType'] = $permissionType;
		$data['permissionTypeId'] = $id;

		$data['instance'] =  $this->instance;

		if($permissionType == DRAWER_PERMISSION) {
			$data['drawer'] = $this->doctrine->em->find('Entity\Drawer', $data['permissionTypeId']);
			$accessLevel = $this->user_model->getAccessLevel(DRAWER_PERMISSION, $data['drawer']);
			if($accessLevel < PERM_CREATEDRAWERS) {
				instance_redirect("errorHandler/error/noPermission");
			}
		}
		else {
			$accessLevel = $this->user_model->getAccessLevel(INSTANCE_PERMISSION, $this->instance);
			if($accessLevel < PERM_ADMIN) {
				instance_redirect("errorHandler/error/noPermission");
			}
		}

		if($id == null) {
			if ($permissionType != INSTANCE_PERMISSION) {
				show_404();
			}
			$data['permissionableObject'] = $data['instance'];
		} else {
			$data['permissionableObject'] = $this->doctrine->em->find("Entity\\$permissionType", $id);
		}

		if ($data['permissionableObject'] === null) {
			show_404();
		}
		$data['permissionList'] = $this->doctrine->em->getRepository("Entity\Permission")->findBy([], ["level"=>"ASC"]);
		$data['groupList'] =  $this->instance->getGroups();
		if($permissionType == DRAWER_PERMISSION) {
			// If the drawer permissions are being added, be sure to include the drawer groups to the group list
			if ($data['drawer'] === null) {
				show_404();
			}
			$myGroups = $this->doctrine->em->getRepository("Entity\DrawerGroup")->findBy(["user"=>$this->user_model->user]);
			$sourceGroups = $data['drawer']->getGroups()->toArray();
			foreach ($sourceGroups as $current) {
			    if ( ! in_array($current, $myGroups)) {
        			$myGroups[] = $current;
    			}
			}
			$data['groupList'] = $myGroups;

		}
		$data['permissions']  = $data['permissionableObject']->getPermissions();

		$data['myUsers'] = $this->doctrine->em->getRepository("Entity\User")->findBy(["createdBy"=>$this->user_model->user]);



		$this->template->title = 'Edit ' . ucfirst($permissionType) . ' Permissions';
		$this->template->content->view('permissions/edit', $data);
		$this->template->publish();

	}

	public function update()
	{
		//TODO Permissions checking
		if($this->config->item('enableCaching')) {
			$this->doctrineCache->setNamespace('userCache_');
			$this->doctrineCache->deleteAll();
		}
		$permissionTypeId = $this->input->post('permissionTypeId');
		$permissionType = $this->input->post('permissionType');


		if($permissionType == INSTANCE_PERMISSION || $permissionType == COLLECTION_PERMISSION) {

			$accessLevel = $this->user_model->getAccessLevel(INSTANCE_PERMISSION, $this->instance);
			if($accessLevel < PERM_ADMIN) {
				instance_redirect("errorHandler/error/noPermission");
			}
		}
		elseif($permissionType == DRAWER_PERMISSION) {
			$accessLevel = $this->user_model->getAccessLevel(DRAWER_PERMISSION, $this->doctrine->em->find('Entity\Drawer', $permissionTypeId));
			if($accessLevel < PERM_CREATEDRAWERS) {
				instance_redirect("errorHandler/error/noPermission");
			}
		}



		if($permissionTypeId == null || $permissionType == INSTANCE_PERMISSION) {
			if ($permissionType != INSTANCE_PERMISSION) {
				show_404();
			}
			$permissionableObject = $this->instance;
		} else {
			$permissionableObject = $this->doctrine->em->find("Entity\\$permissionType", $permissionTypeId);
		}

		if ($permissionableObject === null) {
			show_404();
		}

		$permissions = $permissionableObject->getPermissions();
		$permissionArray = $this->input->post('permission');
		foreach ($permissions as $permissionObject) {

			if(isset($permissionArray[$permissionObject->getId()])) {
				$permission = $this->doctrine->em->find('Entity\Permission', $permissionArray[$permissionObject->getId()]);
				$permissionObject->setPermission($permission);
			}

		}

		$this->doctrine->em->flush();

		if($permissionTypeId == null) {
			instance_redirect("permissions/edit/$permissionType");
		} else {
			instance_redirect("permissions/edit/$permissionType/$permissionTypeId");
		}

	}

	private function addGroup($permissionType=null, $permissionTypeId=null, $groupId=null) {

		if(!$permissionType || !$permissionTypeId || !$groupId) {
			$permissionType = $this->input->post("permissionType");
			$permissionTypeId = $this->input->post("permissionTypeId");
			$groupId = $this->input->post("groupId");
		}

		if($permissionType == INSTANCE_PERMISSION || $permissionType == COLLECTION_PERMISSION) {

			$accessLevel = $this->user_model->getAccessLevel(INSTANCE_PERMISSION, $this->instance);
			if($accessLevel < PERM_ADMIN) {
				instance_redirect("errorHandler/error/noPermission");
			}
		}
		elseif($permissionType == DRAWER_PERMISSION) {
			$accessLevel = $this->user_model->getAccessLevel(DRAWER_PERMISSION, $this->doctrine->em->find('Entity\Drawer', $permissionTypeId));
			if($accessLevel < PERM_CREATEDRAWERS) {
				instance_redirect("errorHandler/error/noPermission");
			}
		}
		else {
			instance_redirect("errorHandler/error/noPermission");
		}


		if(!$groupId) {
			if($permissionTypeId == null) {
				instance_redirect("permissions/edit/$permissionType");
			} else {
				instance_redirect("permissions/edit/$permissionType/$permissionTypeId");
			}
		}
		$defaultPermission = $this->doctrine->em->getRepository("Entity\Permission")->findOneBy(["level"=>PERM_NOPERM]);

		if($permissionType == DRAWER_PERMISSION) {
			$groupObject = $this->doctrine->em->find("Entity\DrawerGroup", $groupId);
			$permissionObject = new Entity\DrawerPermission;
			$permissionObject->setGroup($groupObject);
			$permissionObject->setDrawer($this->doctrine->em->getReference('Entity\Drawer', $permissionTypeId));

		}
		elseif($permissionType == COLLECTION_PERMISSION) {
			$groupObject = $this->doctrine->em->find("Entity\InstanceGroup", $groupId);
			$permissionObject = new Entity\CollectionPermission;
			$permissionObject->setGroup($groupObject);
			$permissionObject->setCollection($this->doctrine->em->getReference('Entity\Collection', $permissionTypeId));
		}
		elseif($permissionType == INSTANCE_PERMISSION) {
			$groupObject = $this->doctrine->em->find("Entity\InstanceGroup", $groupId);
			$permissionObject = new Entity\InstancePermission;
			$permissionObject->setGroup($groupObject);
			$permissionObject->setInstance($this->instance);
		}
		$permissionObject->setPermission($defaultPermission);
		$this->doctrine->em->persist($permissionObject);
		$this->doctrine->em->flush();
		if($permissionTypeId == null) {
			instance_redirect("permissions/edit/$permissionType");
		} else {
			instance_redirect("permissions/edit/$permissionType/$permissionTypeId");
		}
	}



	private function deleteGroup() {
		$permissionType = $this->input->post("permissionType");
		$permissionTypeId = $this->input->post("permissionTypeId");


		if($permissionType == INSTANCE_PERMISSION || $permissionType == COLLECTION_PERMISSION) {

			$accessLevel = $this->user_model->getAccessLevel(INSTANCE_PERMISSION, $this->instance);
			if($accessLevel < PERM_ADMIN) {
				instance_redirect("errorHandler/error/noPermission");
			}
		}
		elseif($permissionType == DRAWER_PERMISSION) {
			$accessLevel = $this->user_model->getAccessLevel(DRAWER_PERMISSION, $this->doctrine->em->find('Entity\Drawer', $permissionTypeId));
			if($accessLevel < PERM_CREATEDRAWERS) {
				instance_redirect("errorHandler/error/noPermission");
			}
		}
		$groupId = $this->input->post("groupId");
		if(!$groupId) {
			if($permissionTypeId == null) {
				instance_redirect("permissions/edit/$permissionType");
			} else {
				instance_redirect("permissions/edit/$permissionType/$permissionTypeId");
			}
		}
		if($permissionType == DRAWER_PERMISSION) {
			$groupObject = $this->doctrine->em->find("Entity\DrawerGroup", $groupId);


		}
		elseif($permissionType == COLLECTION_PERMISSION) {
			$groupObject = $this->doctrine->em->find("Entity\InstanceGroup", $groupId);

		}
		elseif($permissionType == INSTANCE_PERMISSION) {
			$groupObject = $this->doctrine->em->find("Entity\InstanceGroup", $groupId);

		}
		$this->doctrine->em->remove($groupObject);
		$this->doctrine->em->flush();
		if($permissionTypeId == null) {
			instance_redirect("permissions/edit/$permissionType");
		} else {
			instance_redirect("permissions/edit/$permissionType/$permissionTypeId");
		}

	}

	public function modifyGroup()
	{
		if($this->input->post("submit") == "add") {
			$this->addGroup();
		}
		else if($this->input->post("submit") == "delete") {
			$this->deleteGroup();
		}
		else if($this->input->post("submit") == "edit") {
			if($this->config->item('enableCaching')) {
				$this->doctrineCache->setNamespace('userCache_');
				$this->doctrineCache->deleteAll();
			}
			$permissionType = $this->input->post("permissionType");
			$permissionTypeId = $this->input->post("permissionTypeId");
			$groupId = $this->input->post("groupId");

			$this->newGroup($permissionType, $permissionTypeId, $groupId);
		}
	}

	/**
	 * NewGroup creates either instance groups or drawers groups
	 * Collections look like they can have their own groups, but in reality, they just make instance groups
	 */

	public function newGroup($permissionType, $permissionTypeId = null, $groupId=null)
	{
		$data['permissionType'] = $permissionType;
		$data['permissionTypeId'] = $permissionTypeId;
		$data['instance'] =  $this->instance;

		if($permissionType == INSTANCE_PERMISSION || $permissionType == COLLECTION_PERMISSION) {

			$accessLevel = $this->user_model->getAccessLevel(INSTANCE_PERMISSION, $this->instance);
			if($accessLevel < PERM_ADMIN) {
				instance_redirect("errorHandler/error/noPermission");
			}
		}
		elseif($permissionType == DRAWER_PERMISSION) {
			$accessLevel = $this->user_model->getAccessLevel(DRAWER_PERMISSION, $this->doctrine->em->find('Entity\Drawer', $permissionTypeId));
			if($accessLevel < PERM_CREATEDRAWERS) {
				instance_redirect("errorHandler/error/noPermission");
			}
		}
		else {
			instance_redirect("errorHandler/error/noPermission");
		}

		if($data['permissionTypeId'] == null) {
			if ($permissionType != INSTANCE_PERMISSION) {
				show_404();
			}
		} else {
			if($permissionType == INSTANCE_PERMISSION) {
				$data['instance'] = $this->doctrine->em->find('Entity\Instance', $data['permissionTypeId']);
			}
		}

		if ($data['instance'] === null) {
			show_404();
		}


		if($groupId) {
			if($permissionType == DRAWER_PERMISSION) {
				$groupObject = $this->doctrine->em->find("Entity\DrawerGroup", $groupId);
			}
			elseif($permissionType == COLLECTION_PERMISSION) {
				$groupObject = $this->doctrine->em->find("Entity\InstanceGroup", $groupId);
			}
			elseif($permissionType == INSTANCE_PERMISSION) {
				$groupObject = $this->doctrine->em->find("Entity\InstanceGroup", $groupId);
			}


			$data['groupId'] = $groupId;
		}
		else {
			$data['groupId'] = "";
			$groupObject = new Entity\InstanceGroup;
		}


		$data['groupObject'] = $groupObject;

		// TODO The groups are different with drawers
		// TODO Store the groupTypes in the database somewhere. They are hard-coded throughout this controller and the views as:
		//		Class, User, JobCode.

		// There's probably a better way to do this separation, but this seems to make the most sense currently.
		$data['groupCourse'] = $this->doctrine->em->getRepository("Entity\InstanceGroup")->findBy(['group_type' => COURSE_TYPE, 'instance' => $this->instance]);
		$data['groupUser'] = $this->doctrine->em->getRepository("Entity\InstanceGroup")->findBy(['group_type' => USER_TYPE, 'instance' => $this->instance]);
		$data['groupJobCode'] = $this->doctrine->em->getRepository("Entity\InstanceGroup")->findBy(['group_type' => JOB_TYPE, 'instance' => $this->instance]);
		$data['groupUnits'] = $this->doctrine->em->getRepository("Entity\InstanceGroup")->findBy(['group_type' => UNIT_TYPE, 'instance' => $this->instance]);

		if($permissionType == DRAWER_PERMISSION) {
			// If the drawer permissions are being added, be sure to include the drawer groups
			$data['drawer'] = $this->doctrine->em->find('Entity\Drawer', $data['permissionTypeId']);

			if ($data['drawer'] === null) {
				show_404();
			}
			$myGroups = $this->doctrine->em->getRepository("Entity\DrawerGroup")->findBy(["user"=>$this->user_model->user]);
			$sourceGroups = $data['drawer']->getGroups()->toArray();
			foreach ($sourceGroups as $current) {
			    if ( ! in_array($current, $myGroups)) {
        			$myGroups[] = $current;
    			}
			}
			$data['groupList'] = $myGroups;
		}

		$data['permissionList'] = $this->doctrine->em->getRepository("Entity\Permission")->findAll();

		$this->template->title = 'New ' . ucfirst($permissionType) . ' Permissions';
		$this->template->loadJavascript(["handlebars-v1.1.2", "groupCreation"]);
		$this->template->content->view('permissions/new_group', $data);
		$this->template->content->view('handlebarsTemplates');
		$this->template->publish();

	}

	public function createGroup()
	{
		if($this->config->item('enableCaching')) {
			$this->doctrineCache->setNamespace('userCache_');
			$this->doctrineCache->deleteAll();
		}

		$instance =  $this->instance;

		$permissionType = $this->input->post('permissionType');
		$permissionTypeId = $this->input->post('permissionTypeId');
		$groupType = $this->input->post('groupType');
		$groupValue = $this->input->post("groupValue");
		$groupId = $this->input->post("groupId");

		if($permissionType == INSTANCE_PERMISSION) {
			if($groupId) {
				$groupObject = $this->doctrine->em->find("Entity\InstanceGroup", $groupId);
				foreach($groupObject->getGroupValues() as $existingGroup) {
					$groupObject->removeGroupValue($existingGroup);
					$this->doctrine->em->remove($existingGroup);
				}

			}
			else {
				$groupObject = new Entity\InstanceGroup();
			}

			if($permissionTypeId != null) {
				$instance = $this->doctrine->em->find('Entity\Instance', $permissionTypeId);
			}
			if($instance === null) {
				show_404();
			} else {
				$groupObject->setInstance($instance);
			}
		} else if ($permissionType == COLLECTION_PERMISSION) {

			// there's really no such thing as a collection group.  It's just an instance group.

			if($groupId) {
				$groupObject = $this->doctrine->em->find("Entity\InstanceGroup", $groupId);
				foreach($groupObject->getGroupValues() as $existingGroup) {
					$groupObject->removeGroupValue($existingGroup);
					$this->doctrine->em->remove($existingGroup);
				}

			}
			else {
				$groupObject = new Entity\InstanceGroup();
			}

			$groupObject = new Entity\InstanceGroup();
			$groupObject->setInstance($this->instance);
		} else if ($permissionType == DRAWER_PERMISSION) {

			if($groupId) {
				$groupObject = $this->doctrine->em->find("Entity\DrawerGroup", $groupId);
				foreach($groupObject->getGroupValues() as $existingGroup) {

					$groupObject->removeGroupValue($existingGroup);
					$this->doctrine->em->remove($existingGroup);
				}
				$this->doctrine->em->persist($groupObject);
				$this->doctrine->em->flush();

			}
			else {
				$groupObject = new Entity\DrawerGroup();

			}

			$drawer = $this->doctrine->em->find('Entity\Drawer', $permissionTypeId);
			if(!in_array($drawer, $groupObject->getDrawer()->toArray())) {
				$groupObject->addDrawer($drawer);
			}

			$groupObject->setUser($this->user_model->user);

		} else {
			show_404();
		}

		if($permissionType == INSTANCE_PERMISSION || $permissionType == COLLECTION_PERMISSION) {

			$accessLevel = $this->user_model->getAccessLevel(INSTANCE_PERMISSION, $this->instance);
			if($accessLevel < PERM_ADMIN) {
				instance_redirect("errorHandler/error/noPermission");
			}
		}
		elseif($permissionType == DRAWER_PERMISSION) {
			$accessLevel = $this->user_model->getAccessLevel(DRAWER_PERMISSION, $drawer);
			if($accessLevel < PERM_CREATEDRAWERS) {
				instance_redirect("errorHandler/error/noPermission");
			}
		}
		else {
			instance_redirect("errorHandler/error/noPermission");
		}



		$groupObject->setGroupType($groupType);

		if(($groupType == USER_TYPE|| $groupType == COURSE_TYPE || $groupType == UNIT_TYPE || $groupType == JOB_TYPE) && $groupValue == "") {
			instance_redirect("permissions/newGroup/" .$permissionType ."/".$permissionTypeId );
		}

		if($groupType == REMOTE_TYPE || $groupType == AUTHED_TYPE || $groupType == ALL_TYPE) {
			$groupObject->setGroupValue(1);
		}

		foreach($groupValue as $individualValue) {
			if($individualValue === "") {
				continue;
			}
			if($groupType == USER_TYPE && !is_numeric($individualValue)) {
				$individualValue = $this->getLocalUserIdForRemoteUser($individualValue);
			}

			$groupValueObject = new Entity\GroupEntry();

			$groupValueObject->setGroupValue($individualValue);

			$groupObject->addGroupValue($groupValueObject);


		}

		$groupObject->setGroupLabel($this->input->post('groupLabel'));

		$this->doctrine->em->persist($groupObject);

		$this->doctrine->em->flush();

		if(!$groupId) {
			$this->addGroup($permissionType, $permissionTypeId, $groupObject->getId());
		}



		if($permissionTypeId == null) {
			instance_redirect("permissions/edit/$permissionType");
		} else {
			instance_redirect("permissions/edit/$permissionType/$permissionTypeId");
		}
	}


	public function getLocalUserIdForRemoteUser($userId) {
		// assume this a remote user, we need to create them.

		// first, get their info:
		$userArray = $this->user_model->findUserFromLDAP($userId, "umndid");


		if(count($userArray) == 0) {
			// something has gone wrong - this should have been an umndid..
			$this->logging->logError("permissions", "createGroup", "couldn't find a user from the form, " . $userId);
			instance_redirect("errorHandler/error/invalidUser");
		}
		else {
			$userObject = $userArray[0];
		}

		//first, try to find them locally
		//

		$result = $this->doctrine->em->getRepository("Entity\User")->findBy(["username"=>$userObject->getUsername()]);
		if($result && count($result) > 0) {
			$groupValue = $result[0]->getId();
		}
		else {

			$userObject->setUserType("Remote");
			$userObject->setCreatedAt(new \DateTime("now"));
			$userObject->setInstance($this->instance);
			$this->doctrine->em->persist($userObject);
			$this->doctrine->em->flush();
			$groupValue = $userObject->getId();

		}

		return $groupValue;
	}



	public function delete($permissionType, $permissionId, $permissionTypeId = null)
	{
		//TODO Permissions checking
		$upperDrawer = ucfirst($permissionType);
		$permission = $this->doctrine->em->find("Entity\\{$upperDrawer}Permission", $permissionId);
		if ($permission === null) {
			show_404();
		}


		if($permissionType == INSTANCE_PERMISSION || $permissionType == COLLECTION_PERMISSION) {

			$accessLevel = $this->user_model->getAccessLevel(INSTANCE_PERMISSION, $this->instance);
			if($accessLevel < PERM_ADMIN) {
				instance_redirect("errorHandler/error/noPermission");
			}
		}
		elseif($permissionType == DRAWER_PERMISSION) {
			$accessLevel = $this->user_model->getAccessLevel(DRAWER_PERMISSION, $this->doctrine->em->find('Entity\Drawer', $permissionTypeId));
			if($accessLevel < PERM_CREATEDRAWERS) {
				instance_redirect("errorHandler/error/noPermission");
			}
		}



		$this->doctrine->em->remove($permission);
		$this->doctrine->em->flush();

		if($permissionTypeId == null) {
			instance_redirect("permissions/edit/$permissionType");
		} else {
			instance_redirect("permissions/edit/$permissionType/$permissionTypeId");
		}
	}

	public function addUser()
	{
		$this->template->loadJavascript(["bootstrap-datepicker"]);
		$this->template->loadCSS(["datepicker"]);

		$tempUser = new Entity\User;
		$tempUser->setUserType("Local");

		$this->template->content->view('permissions/addUser', ["user"=>$tempUser]);
		$this->template->publish();
	}



	public function editUser($userId = null) {
		if(!$userId) {
			instance_redirect("/");
		}
		if(!is_numeric($userId)) {
			$user = $this->doctrine->em->getRepository("Entity\User")->findOneBy(["username"=>$userId]);
		} else {
			$user = $this->doctrine->em->find("Entity\User", $userId);
		}

		if($user === null) {
			//must be a remote user
			$userId = $this->makeUserLocal($userId);
			instance_redirect("permissions/editUser/" . $userId);
		}

		/**
		 * at one point, the thinking was that a local user could only be edited on the instance they were
		 * created on.  Starting to think that doesn't make sense, so commenting it out.
		 */

		// if($user->getUserType() == "Local" && $user->getInstance() != $this->instance) {
		// 	instance_redirect("errorHandler/error/wrongInstance");
		// 	return;
		// }

		$accessLevel = $this->user_model->getAccessLevel(INSTANCE_PERMISSION,$this->instance);

		if($accessLevel < PERM_ADMIN && $user->getCreatedBy() != $this->user_model->user && $user->getId() != $this->user_model->user->getId()) {
			$this->logging->logError("editUser", "User " . $this->user_model->user->getId() . " tried to edit" . $userId);
			instance_redirect("errorHandler/error/noPermission");
		}

		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->from("Entity\Asset", 'a')
			->select("a")
			->where("a.createdBy = :userId")
			->setParameter(":userId", (int)$userId)
			->andWhere("a.assetId IS NOT NULL")
			->orderBy("a.modifiedAt", "DESC")
			->setMaxResults(20);

		$assets = $qb->getQuery()->execute();

		$this->load->model("asset_model");

		$hiddenAssetArray = array();

		foreach($assets as $entry) {
			$this->asset_model->loadAssetFromRecord($entry);
			$hiddenAssetArray[] = ["objectId"=>$this->asset_model->getObjectId(), "title"=>$this->asset_model->getAssetTitle(true), "readyForDisplay"=>$this->asset_model->getGlobalValue("readyForDisplay"), "modifiedDate"=>$this->asset_model->getGlobalValue("modified")];
		}
		$instanceList = $this->doctrine->em->getRepository("Entity\Instance")->findAll();


		$this->template->loadJavascript(["bootstrap-datepicker"]);
		$this->template->loadCSS(["datepicker"]);

		$this->template->content->view('permissions/addUser', ["user"=>$user, "instanceList"=>$instanceList]);
		$this->template->content->view('user/hiddenAssets', ["hiddenAssets"=>$hiddenAssetArray, "isOffset"=>false]);
		$this->template->publish();

	}

	/**
	 * used by the API to set a default instance
	 */
	public function updateAPIinstance() {


		$targetInstance = $this->doctrine->em->getReference('Entity\Instance', $this->input->post("apiInstance"));

		$this->user_model->user->setApiInstance($targetInstance);
		$this->doctrine->em->persist($this->user_model->user);
		$this->doctrine->em->flush();
		redirect($this->input->post("redirectURL"));

	}


	private function makeUserLocal($umndid) {

		$user = $this->doctrine->em->getRepository("Entity\User")->findBy(["username"=>$umndid, "userType"=>"Remote"]);
		if($user && count($user) >0){
			return $user{0}->getId();
		}

		$user = $this->user_model->createUserFromRemote($umndid);
		return $user->getId();
	}

	public function removeUser()
	{
		// TODO: manually clean up permission groups
		//
		if(is_numeric($this->input->post("userId"))) {
			$user = $this->doctrine->em->find("Entity\User", $this->input->post("userId"));

			$results = $this->doctrine->em->getRepository("Entity\Log")->findBy(["user"=>$user]);
			foreach($results as $result) {
				$result->setUser(null);
				$this->doctrine->em->persist($result);
			}

			$this->doctrine->em->remove($user);

			$this->doctrine->em->flush();
			$this->template->content = "User Deleted";
		}
		$this->template->publish();
	}

	public function saveUser()
	{
		if($this->config->item('enableCaching')) {
			$this->doctrineCache->setNamespace('userCache_');
		}

		if(is_numeric($this->input->post("userId"))) {
			if($this->config->item('enableCaching')) {
				$this->doctrineCache->delete($this->input->post("userId"));
			}
			$user = $this->doctrine->em->find("Entity\User", $this->input->post("userId"));
			$this->template->content = "User Updated.";
		}
		else {
			$user = $this->doctrine->em->getRepository("Entity\User")->findBy(["username"=>$this->input->post("username"), "userType"=>"Local"]);
			if($user && count($user) >0){
				$this->template->content = "Username not available, please go back and try again.";
				$this->template->publish();
				return;
			}

			$user = new Entity\User;


			$user->setUserType("Local");

			$user->setCreatedBy($this->user_model->user);
			$user->setCreatedAt(new \DateTime("now"));

			$this->template->content = "User Added.";
		}

		if($this->input->post("password") != "dontchangeme") {
			$user->setPassword(sha1($this->config->item('encryption_key').$this->input->post("password")));
		}
		$accessLevel = $this->user_model->getAccessLevel(INSTANCE_PERMISSION,$this->instance);

		if($accessLevel < PERM_ADMIN && $user->getCreatedBy() != $this->user_model->user && $user->getId() != $this->user_model->user->getId()) {
			$this->logging->logError("editUser", "User " . $this->user_model->user->getId() . " tried to edit" . $userId);
			instance_redirect("errorHandler/error/noPermission");
		}

		if($this->input->post("username")) {
			$user->setUsername($this->input->post("username"));
		}

		$user->setDisplayName($this->input->post("label"));

		$user->setEmail($this->input->post("email"));

		$user->setInstance($this->instance);


		if($this->input->post("apiInstance") != 0) {
			$user->setApiInstance($this->doctrine->em->getReference('Entity\Instance', $this->input->post("apiInstance")));
		}


		if($this->input->post("expires")) {
			$user->setHasExpiry(($this->input->post("hasExpiry")=="On")?true:false);
			$expiration = new \DateTime($this->input->post("expires"));
			$user->setExpires($expiration);
		}


		if($this->input->post("isSuperAdmin")) {
			$user->setIsSuperAdmin($this->input->post("isSuperAdmin")?true:false);
		}
		$user->setFastUpload($this->input->post("fastUpload")?true:false);
		$this->doctrine->em->persist($user);
		$this->doctrine->em->flush();

		$this->template->publish();
	}

	public function userAutocompleter() {

		$accessLevel = $this->user_model->getAccessLevel(INSTANCE_PERMISSION,$this->instance);

		if($accessLevel < PERM_ADMIN && $user->getCreatedBy() != $this->user_model->user) {
			$this->logging->logError("editUser", "User " . $this->user_model->user->getId() . " tried to edit" . $userId);
			instance_redirect("errorHandler/error/noPermission");
		}
		$groupType = $this->input->post("groupType");
		$groupValue = $this->input->post("groupValue");

   		$outputArray = array();
		if($groupType == USER_TYPE) {

			$result = $this->doctrine->em->getRepository("Entity\User")->createQueryBuilder('u')
   				->where('u.instance= :instance')
   				->andWhere('u.displayName LIKE :name')
   				->setParameter('instance', $this->instance)
   				->setParameter('name', '%'.$groupValue.'%')
   				->getQuery()
   				->getResult();

   				//TODO: limit number of users
   				//TODO: only searhc local users
   				//

   			$userMatches = $this->doctrine->em->getRepository("Entity\User")->findBy(["username"=>$groupValue]);

   			foreach($userMatches as $user) {
   				$tempArray = ["name"=>$user->getDisplayName(), "email"=>$user->getEmail(), "completionId"=>$user->getId(), "username"=>$user->getUsername()];
   				$outputArray[$user->getId()] = $tempArray;
   			}
   			$i=0;
   			foreach($result as $user) {
   				$tempArray = ["name"=>$user->getDisplayName(), "email"=>$user->getEmail(), "completionId"=>$user->getId(), "username"=>$user->getUsername()];
   				if(!array_key_exists($user->getId(), $outputArray)) {
   					$outputArray[$user->getId()] = $tempArray;
   				}
   				$i++;
   				if($i>10) {
   					break;
   				}
   			}

   			// check for x500 match
   			$userMatches = $this->user_model->findUserFromLDAP($groupValue, "cn");
			foreach($userMatches as $user) {
				$tempArray = ["name"=>$user->getDisplayName(), "email"=>$user->getEmail(), "completionId"=>$user->getId(), "username"=>$user->getUsername()];

				$duplicate = false;
				foreach($outputArray as $entry) {
					if($entry["username"] == $user->getUsername()) {
						$duplicate = true;
					}
				}

				if(!$duplicate) {
					array_unshift($outputArray, $tempArray);
				}
			}

			// now wildcard names
			$userMatches = $this->user_model->findUserFromLDAP("*".str_replace(" ", "* ", $groupValue) . "*", "displayname");

			$i = 0;
			foreach($userMatches as $user) {

				$tempArray = ["name"=>$user->getDisplayName(), "email"=>$user->getEmail(), "completionId"=>$user->getId(), "username"=>$user->getUsername()];

				$duplicate = false;
				foreach($outputArray as $entry) {
					if($entry["username"] == $user->getUsername()) {
						$duplicate = true;
					}
				}

				if(!$duplicate) {
					$outputArray[] = $tempArray;
				}

				if($i > 10) {
					break;
				}
				$i++;
			}


		}

		echo json_encode(["success"=>true, "matches"=>$outputArray]);

	}


	public function instanceHandlerGroups() {
		if($this->config->item('enableCaching')) {
			$this->doctrineCache->setNamespace('userCache_');
			$this->doctrineCache->deleteAll();
		}

		$accessLevel = $this->user_model->getAccessLevel(INSTANCE_PERMISSION, $this->instance);
		if($accessLevel < PERM_ADMIN) {
			instance_redirect("errorHandler/error/noPermission");
		}


		$possibleHandlers = $this->filehandler_router->getAllHandlers();

		$handlerNames = array_map(function($n) { return get_class($n);}, $possibleHandlers);

		if (!empty($_POST)) {

			foreach($this->instance->getHandlerPermissions() as $permission) {
				$this->instance->removeHandlerPermission($permission);
				$this->doctrine->em->remove($permission);
			}
			foreach($handlerNames as $handlerName) {
				$newPermission = new Entity\InstanceHandlerPermissions;
				$newPermission->setHandlerName($handlerName);
				$newPermission->setPermissionGroup($this->input->post($handlerName));
				$newPermission->setInstance($this->instance);
				$this->instance->addHandlerPermission($newPermission);
			}

			$this->doctrine->em->flush();


		}


		$currentPermissions = $this->instance->getHandlerPermissions();
		$groupArray = array();
		foreach($currentPermissions as $permission) {
			$groupArray[$permission->getHandlerName()] = $permission->getPermissionGroup();
		}
		$permissionArray = array();
		foreach($handlerNames as $handlerName) {

			if(array_key_exists($handlerName, $groupArray)) {
				$permissionArray[$handlerName] = $groupArray[$handlerName];
			}
			else {
				$permissionArray[$handlerName] = 0;
			}
		}


		$data['handlerPermissions'] = $permissionArray;


		$this->template->title = 'Edit Handler Permissions';
		$this->template->content->view('permissions/handler_permissions', $data);
		$this->template->publish();


	}

}

