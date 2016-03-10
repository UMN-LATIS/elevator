<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Drawers extends Instance_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{

	}

	public function listDrawers() {
		$drawers = $this->user_model->getDrawers();
		$this->template->content->view("listDrawers", ["drawers"=>$drawers]);
		$this->template->publish();
	}


	public function viewDrawer($drawerId) {


		$accessLevel = $this->user_model->getAccessLevel("drawer",$this->doctrine->em->getReference("Entity\Drawer", $drawerId));

		if($accessLevel == PERM_NOPERM) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$this->user_model->addRecentDrawer($this->doctrine->em->find('Entity\Drawer', $drawerId));

		$this->template->content->view("search");

		$this->template->javascript->add("//maps.google.com/maps/api/js?libraries=geometry");

		$jsLoadArray = ["handlebars-v1.1.2", "jquery.gomap-1.3.2", "mapWidget", "markerclusterer", "sugar","drawers", "galleria-1.3.3", "search", "loadDrawer"];
		$this->template->loadJavascript($jsLoadArray);

		$this->template->addToDrawer->view("drawers/edit_drawer",["drawerId"=>$drawerId]);
		$this->template->content->view("drawers/drawerModal");

		$this->template->publish();
	}


	public function downloadDrawer($drawerId) {
		$accessLevel = $this->user_model->getAccessLevel("drawer",$this->doctrine->em->getReference("Entity\Drawer", $drawerId));

		if($accessLevel < PERM_ORIGINALSWITHOUTDERIVATIVES) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$drawer = $this->doctrine->em->find("Entity\Drawer", $drawerId);
		$this->load->model("s3_model");
		$this->s3_model->loadFromInstance($this->instance);
		$objectInfo = $this->s3_model->objectInfo("drawer/".$drawerId.".zip");
		if($objectInfo && !$drawer->getChangedSinceArchive()) {
			redirect($this->s3_model->getProtectedURL("drawer/".$drawerId.".zip", "drawer-".$drawerId.".zip"));
		}
		else {
			$drawer->setChangedSinceArchive(false);
			$this->doctrine->em->flush();
			$pheanstalk = new Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));
			$newTask = json_encode(["drawerId"=>$drawerId, "userContact"=>$this->user_model->getEmail(), "instance"=>$this->instance->getId()]);
			$jobId= $pheanstalk->useTube('archiveTube')->put($newTask, NULL, 1, 900); // run a 15 minute TTR because zipping all these could take a while.

			$this->template->content->view("drawers/downloadDrawer");
			$this->template->publish();


		}


	}

	public function getDrawer($drawerId) {
		$drawer = $this->doctrine->em->find("Entity\Drawer", $drawerId);

		$accessLevel = $this->user_model->getAccessLevel("drawer",$drawer);

		if($accessLevel == PERM_NOPERM) {
			$this->errorhandler_helper->callError("noPermission");;
		}

		$this->load->model("search_model");
		$this->load->model("asset_model");
		if($drawer) {
			// TODO: understand doctrine hydrate better so you don't need this toArray
			$drawerItems = $drawer->getItems();
			$mappedArray = array();

			foreach($drawerItems as $drawerItem) {

				$mappedArray[$drawerItem->getAsset()] = $drawerItem;
			}
			// we just want assetIds, walk the array to remap it
			$mappedArray["searchId"] = null;
			$resultArray = $this->search_model->processSearchResults(["searchText"=>""], ["searchResults"=>array_keys($mappedArray)]);

			// now reinject excerpts
			// we do this by looping through all our drawerItems, hydrating them with the search results, and then
			// if necessary adding the excerpts.
			$outputArray = array();
			foreach($drawerItems as $drawerItem) {
				$matchItem = array();
				foreach($resultArray["matches"] as $match) {
					if($match["objectId"] == $drawerItem->getAsset()){
						$matchItem = $match;
					}
				}

				if($drawerItem->getExcerptAsset() != NULL) {
					$matchItem["excerpt"] = true;
					$matchItem["excerptId"] = $drawerItem->getId();
					$matchItem["excerptAsset"] = $drawerItem->getExcerptAsset();
					$matchItem["excerptLabel"] = $drawerItem->getExcerptLabel();
				}
				$outputArray[] = $matchItem;
			}
			$resultArray["matches"] = $outputArray;
			$resultArray["totalResults"] = count($outputArray);
			$resultArray["drawerId"] = $drawerId;
			echo json_encode($resultArray);
		}
		else {
			echo "fail";
		}

	}



	public function removeFromDrawer($drawerId, $assetId) {
		$accessLevel = $this->user_model->getAccessLevel("drawer",$this->doctrine->em->getReference("Entity\Drawer", $drawerId));

		if($accessLevel < PERM_CREATEDRAWERS) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$drawerItem = $this->doctrine->em->getRepository("Entity\DrawerItem")->findOneBy(['drawer'=>$this->doctrine->em->getReference("Entity\Drawer", $drawerId), 'asset' => $assetId]);

		$this->doctrine->em->remove($drawerItem);
		$this->doctrine->em->flush();
		instance_redirect("drawers/viewDrawer/".$drawerId);
	}

	public function removeExcerpt($drawerId, $excerptId) {
		$accessLevel = $this->user_model->getAccessLevel("drawer",$this->doctrine->em->getReference("Entity\Drawer", $drawerId));

		if($accessLevel < PERM_CREATEDRAWERS) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$drawerItem = $this->doctrine->em->getRepository("Entity\DrawerItem")->find($excerptId);
		$this->doctrine->em->remove($drawerItem);
		$this->doctrine->em->flush();
		instance_redirect("drawers/viewDrawer/".$drawerId);
	}

	public function addToDrawer() {
		$drawerId = $this->input->post("drawerList");
		$drawer = $this->doctrine->em->find('Entity\Drawer', $drawerId);

		$drawer->setChangedSinceArchive(true);
		$accessLevel = $this->user_model->getAccessLevel("drawer",$drawer);

		if($accessLevel < PERM_CREATEDRAWERS) {
			$this->errorhandler_helper->callError("noPermission");
		}

		if($this->input->post("excerptId")) {
			$excerpt = $this->doctrine->em->getRepository("Entity\DrawerItem")->find($this->input->post("excerptId"));
			$newExcerpt = clone $excerpt;
			$newExcerpt->setDrawer($drawer);
			$this->doctrine->em->persist($newExcerpt);

		}
		elseif($this->input->post("fileHandlerId") && $this->input->post("objectId")) {
			$assetId = $this->input->post("objectId");
			$fileHandlerId = $this->input->post("fileHandlerId");
			$startTime = $this->input->post("startTime");
			$endTime = $this->input->post("endTime");
			$label = $this->input->post("label");
			$this->addExcerptToDrawer($assetId, $fileHandlerId, $startTime, $endTime, $label, $drawer);
		}
		elseif($this->input->post("objectId")) {
			$assetId = $this->input->post("objectId");
			$this->addItemToDrawer($assetId, $drawer);
		}
		elseif($this->input->post("objectArray")) {
			$objectArray = json_decode($this->input->post("objectArray"));
			foreach($objectArray as $assetId) {
				$this->addItemToDrawer($assetId, $drawer);
			}
		}
		$this->doctrine->em->flush();
	}


	private function addItemToDrawer($assetId, $drawer) {

		$items = $drawer->getItems();
		if(count($items)>0) {
			foreach($items as $item) {
				if($item->getAsset() == $assetId) {
					return;
				}
			}
		}
		$drawerItem = new Entity\DrawerItem;
		$drawerItem->setAsset($assetId);
		$drawerItem->setDrawer($drawer);
		$this->doctrine->em->persist($drawerItem);
	}

	private function addExcerptToDrawer($assetId, $fileHandlerId, $startTime, $endTime, $label, $drawer) {

		$drawerItem = new Entity\DrawerItem;
		$drawerItem->setAsset($assetId);
		$drawerItem->setDrawer($drawer);
		$drawerItem->setExcerptAsset($fileHandlerId);
		$drawerItem->setExcerptStart($startTime);
		$drawerItem->setExcerptEnd($endTime);
		$drawerItem->setExcerptLabel($label);
		$this->doctrine->em->persist($drawerItem);
	}


	public function delete($drawerId) {
		$drawer = $this->doctrine->em->find("Entity\Drawer",$drawerId);

		$accessLevel = $this->user_model->getAccessLevel("drawer", $drawer);

		if($accessLevel < PERM_CREATEDRAWERS) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$this->doctrine->em->remove($drawer);
		$this->doctrine->em->flush();
		instance_redirect("/");


	}
	public function addDrawer() {
		$accessLevel = $this->user_model->getAccessLevel("instance", $this->instance);

		$accessLevel = max($accessLevel, $this->user_model->getMaxCollectionPermission());

		if($accessLevel < PERM_CREATEDRAWERS) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$drawer = new Entity\Drawer;
		$drawer->setTitle($this->input->post("drawerTitle"));
		$drawer->setInstance($this->instance);
		$this->doctrine->em->persist($drawer);

		// see if this user already has a drawer group
		//

		// $groupObject = $this->doctrine->em->getRepository("Entity\DrawerGroup")->findOneBy(["user"=>$this->user_model->user, "group_type"=>"User","group_value"=>$this->user_model->getId()]);
		$groupObject = $this->user_model->getPermissions("DrawerGroup", "User", $this->user_model->getId(), $this->user_model->user);

		if(count($groupObject)) {
			$groupObject = array_shift($groupObject);
		}

		if(!$groupObject) {
			$groupObject = new Entity\DrawerGroup;
			$groupObject->setUser($this->user_model->user);
			$groupObject->setGroupType("User");
			$groupValueObject = new Entity\GroupEntry();

			$groupValueObject->setGroupValue($this->user_model->getId());

			$groupObject->addGroupValue($groupValueObject);
			$groupObject->setGroupLabel($this->user_model->getDisplayName());
			$this->doctrine->em->persist($groupObject);
		}

		$permission = new Entity\DrawerPermission;
		$permission->setDrawer($drawer);
		$permission->setGroup($groupObject);


		$permissionType = $this->doctrine->em->getRepository("Entity\Permission")->findOneBy(["level"=>PERM_CREATEDRAWERS]);
		$permission->setPermission($permissionType);
		$this->doctrine->em->persist($permission);
		$this->doctrine->em->flush();

		$drawerId = $drawer->getId();

		if($this->config->item('enableCaching')) {
			$this->doctrineCache->setNamespace('userCache_');
			$this->doctrineCache->delete($this->user_model->userId);
		}

		echo json_encode(["drawerId"=>$drawer->getId(), "drawerTitle"=>$drawer->getTitle()]);

	}



}

/* End of file  */
/* Location: ./application/controllers/ */