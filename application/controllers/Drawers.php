<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Drawers extends Instance_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		return show_404();
	}

	public function listDrawers($json=false) {
		if ($this->isUsingVueUI() && !$json) {
			$this->template->set_template("vueTemplate");
			$this->template->publish();
			return;
		}

		$drawers = $this->user_model->getDrawers($adminOnly=false, $nonGlobalOnly=true);
		function customSort($a, $b) {
			$aSort = $a->getTitle();
			$bSort = $b->getTitle();
			return strcasecmp($aSort, $bSort);
		}
		usort($drawers, "customSort");	
		if($json) {
			$drawerStructure = [];
			foreach($drawers as $drawer) {
				$drawerStructure[$drawer->getId()] = ["title"=>$drawer->getTitle()];
			}
			return render_json($drawerStructure);
		}
		else {
			$this->template->content->view("listDrawers", ["drawers"=>$drawers]);
			$this->template->publish();
		}
		
	}

	public function viewDrawer($drawerId) {
		if ($this->isUsingVueUI()) {
			$this->template->set_template("vueTemplate");
			$this->template->publish();
			return;
		}


		$accessLevel = $this->user_model->getAccessLevel("drawer",$this->doctrine->em->getReference("Entity\Drawer", $drawerId));

		if($accessLevel == PERM_NOPERM) {
			$this->errorhandler_helper->callError("noPermission");
		}
		
		$this->user_model->addRecentDrawer($this->doctrine->em->find('Entity\Drawer', $drawerId));

		$drawer = $this->doctrine->em->find("Entity\Drawer", $drawerId);
		$this->template->content->view("search/search", ["drawerMode"=>true, "orderBy"=>$drawer->getSortBy()]);

		$this->template->javascript->add("//maps.google.com/maps/api/js?key=". $this->config->item("googleApi") ."&libraries=geometry");

		$jsLoadArray = ["handlebars-v1.1.2", "mapWidget","drawers",  "search", "loadDrawer", "jquery.fullscreen-0.4.1"];
		$this->template->loadJavascript($jsLoadArray);
		$this->template->javascript->add("/assets/js/sly.min.js");
		$this->template->javascript->add("/assets/TimelineJS3/compiled/js/timeline.js");
		$this->template->stylesheet->add("/assets/TimelineJS3/compiled/css/timeline.css");
		$this->template->addToDrawer->view("drawers/edit_drawer",["drawerId"=>$drawerId]);
		$this->template->content->view("drawers/drawerModal");

		$this->template->publish();
	}


	public function downloadDrawer($drawerId, $returnJSON = false) {
		if ($this->isUsingVueUI() && !$returnJSON) {
			return $this->template->publish('vueTemplate');
		}

		if (!$drawerId) {
			return $returnJSON
				? render_json(["error" => "No drawer ID provided"], 400)
				: show_404();
		}

		$drawer = $this->doctrine->em->find("Entity\Drawer", $drawerId);

		if (!$drawer) {
			return $returnJSON
				? render_json(["error" => "Drawer not found"], 404)
				: show_404();
		}

		$accessLevel = $this->user_model->getAccessLevel("drawer", $drawer);

		if ($accessLevel < PERM_ORIGINALSWITHOUTDERIVATIVES) {
			return $returnJSON
				? render_json(["error" => "No permission to download this drawer"], 403)
				: $this->errorhandler_helper->callError("noPermission");
		}

		$this->load->model("s3_model");
		$this->s3_model->loadFromInstance($this->instance);
		$objectInfo = $this->s3_model->objectInfo("drawer/" . $drawerId . ".zip");

		// If the drawer hasn't been changed since archiving
		// and the object info exists, the zip file is ready
		if ($objectInfo && !$drawer->getChangedSinceArchive()) {
			$zipUrl = $this->s3_model->getProtectedURL("drawer/" . $drawerId . ".zip", "drawer-" . $drawerId . ".zip");
			return $returnJSON
				? render_json(["status" => "completed", "url" => $zipUrl])
				: redirect($zipUrl);
		}


		// Reset the changed flag and queue a new job
		$drawer->setChangedSinceArchive(false);
		$this->doctrine->em->flush();

		$newTask = json_encode([
			"drawerId" => $drawerId,
			"userContact" => $this->user_model->getEmail(),
			"instance" => $this->instance->getId()
		]);

		$pheanstalk = new Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));

		// run a 15 minute TTR because zipping all these could take a while
		$jobId = $pheanstalk->useTube('archiveTube')->put($newTask, NULL, 1, 900);

		if ($returnJSON) {
			return render_json(["status" => "accepted", "jobId" => $jobId]);
		}

		$this->template->content->view("drawers/downloadDrawer");
		$this->template->publish();
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
			function customSort($a, $b) {
				$aSort = $a["title"];
				$bSort = $b["title"];
				if(isset($a["excerptLabel"])) {
					$aSort = $a["excerptLabel"];
				}
				if(isset($b["excerptLabel"])) {
					$bSort = $b["excerptLabel"];
				}
				return strcmp($aSort, $bSort);
			}
			if(!$drawer->getSortBy() || $drawer->getSortBy() == "title.raw") {
				usort($outputArray, "customSort");	
			}
			
			
			$resultArray["matches"] = $outputArray;
			$resultArray["totalResults"] = count($outputArray);
			$resultArray["drawerId"] = $drawerId;
			$resultArray["drawerTitle"] = $drawer->getTitle();
			$resultArray['sortBy'] = $drawer->getSortBy();

			return render_json($resultArray);
		}
		else {
			return render_json(["error"=>"fail"]);
		}

	}


	public function removeFromDrawer($drawerId, $assetId, $returnJSON = false) {
		if (!$drawerId) {
			return $returnJSON 
				? render_json(["error" => "Invalid drawer ID."], 400)
			  : $this->errorhandler_helper->callError("noPermission");
		}

		if (!$assetId) {
			return $returnJSON 
				? render_json(["error" => "Invalid asset ID."], 400)
			  : $this->errorhandler_helper->callError("noPermission");
		}

		$drawer = $this->doctrine->em->getReference("Entity\Drawer", $drawerId);

		if (!$drawer) {
			return $returnJSON 
			? render_json(["error" => "Drawer not found."], 404)
			: $this->errorhandler_helper->callError("noPermission");
		}

		$accessLevel = $this->user_model->getAccessLevel("drawer", $drawer);

		if($accessLevel < PERM_CREATEDRAWERS) {
			return $returnJSON
				? render_json(["error" => "You do not have permission to remove items from this drawer."], 403)
				: $this->errorhandler_helper->callError("noPermission");
		}

		$drawerItem = $this->doctrine->em->getRepository("Entity\DrawerItem")->findOneBy(['drawer'=>$this->doctrine->em->getReference("Entity\Drawer", $drawerId), 'asset' => $assetId]);

		// if item is not in drawer, return error
		// or redirect to drawer view
		if (!$drawerItem) {
			return $returnJSON 
				? render_json(["error" => "Asset not found in drawer."], 404)
				: instance_redirect("drawers/viewDrawer/".$drawerId);
		}

		$this->doctrine->em->remove($drawerItem);
		$drawer->setChangedSinceArchive(true);
		$this->doctrine->em->flush();
		
		return $returnJSON
			? render_json(["success"=>true])
			: instance_redirect("drawers/viewDrawer/".$drawerId);
	}

	public function removeExcerpt($drawerId, $excerptId, $json=false) {
		$accessLevel = $this->user_model->getAccessLevel("drawer",$this->doctrine->em->getReference("Entity\Drawer", $drawerId));

		if($accessLevel < PERM_CREATEDRAWERS) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$drawerItem = $this->doctrine->em->getRepository("Entity\DrawerItem")->find($excerptId);
		$this->doctrine->em->remove($drawerItem);
		$this->doctrine->em->flush();
		if($json) {
			return render_json(["success"=>true]);
		}
		else {
			instance_redirect("drawers/viewDrawer/".$drawerId);
		}
		
	}

	public function addToDrawer($shouldReturnJSON = false) {
		$drawerId = $this->input->post("drawerList");
		if (!$drawerId) {
			return render_json(["error" => "Invalid drawer id"], 400);
		}

		$drawer = $this->doctrine->em->find('Entity\Drawer', $drawerId);

		if (!$drawer) {
			return render_json(["error" => "Drawer not found."], 404);
		}

		$accessLevel = $this->user_model->getAccessLevel("drawer",$drawer);

		if($accessLevel < PERM_CREATEDRAWERS) {
			return $shouldReturnJSON
				? render_json(["error" => "You do not have permission to add to this drawer."], 403)
				: $this->errorhandler_helper->callError("noPermission");
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
		$drawer->setChangedSinceArchive(true);
		$this->doctrine->em->flush();
		return render_json(["success"=>true]);
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


	public function delete($drawerId, $shouldReturnJSON = false) {
		$drawer = $this->doctrine->em->find("Entity\Drawer",$drawerId);
		if(!$drawer) {
			return render_json(["error" => "Not found", "status" => 404], 404);
		}

		$accessLevel = $this->user_model->getAccessLevel("drawer", $drawer);
		if ($accessLevel < PERM_CREATEDRAWERS) {
			return $shouldReturnJSON 
				? render_json(["error" => "No permission", "status" => 403], 403)
				: $this->errorhandler_helper->callError("noPermission");
		}

		$this->doctrine->em->remove($drawer);
		$this->doctrine->em->flush();

		return $shouldReturnJSON
			? render_json([ "success" => true])
			: instance_redirect("/");
	}

	public function addDrawer($shouldReturnJSON = false) {
		$accessLevel = $this->user_model->getAccessLevel("instance", $this->instance);

		$accessLevel = max($accessLevel, $this->user_model->getMaxCollectionPermission());

		if($accessLevel < PERM_CREATEDRAWERS) {
			return $shouldReturnJSON 
				? render_json(["error" => "No permission", "status" => 403], 403)
				: $this->errorhandler_helper->callError("noPermission");
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

		$groupObject->addDrawer($drawer);

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

		return render_json(["drawerId"=>$drawer->getId(), "drawerTitle"=>$drawer->getTitle()]);

	}

	public function setSortOrder($drawerId, $sortOrder) {
		if (!$drawerId) {
			return render_json(["error" => "Not found", "status" => 404], 404);
		}

		if (!in_array($sortOrder, ['title.raw', 'custom'])) {
			return render_json(["error" => "Invalid sort order", "status" => 400], 400);
		}

		$drawer = $this->doctrine->em->find("Entity\Drawer", $drawerId);

		if (!$drawer) {
			return render_json(["error" => "Not found", "status" => 404], 404);
		}

		$accessLevel = $this->user_model->getAccessLevel("drawer", $drawer);

		if ($accessLevel < PERM_CREATEDRAWERS) {
			return render_json(["error" => "No permission", "status" => 403], 403);
		}

		$drawer->setSortBy($sortOrder);
		$this->doctrine->em->flush();

		return render_json(["success"=>true]);
	}

	public function setCustomOrder($drawerId) {
		$accessLevel = $this->user_model->getAccessLevel("drawer",$this->doctrine->em->getReference("Entity\Drawer", $drawerId));

		if($accessLevel < PERM_CREATEDRAWERS) {
			$this->errorhandler_helper->callError("noPermission");
		}
		$drawer = $this->doctrine->em->find("Entity\Drawer", $drawerId);

		$orderArray = json_decode($this->input->post("orderArray"));
		if(!is_array($orderArray) || count($orderArray) == 0) {
			return;
		}

		foreach($drawer->getItems() as $item) {
			$sortOrder = null;
			if($item->getExcerptAsset() !== null) {
				$sortOrder = array_search($item->getId(), $orderArray);
			}
			else {
				$sortOrder = array_search($item->getAsset(), $orderArray);
			}

			if($sortOrder !== null) {
				$item->setSortOrder($sortOrder);
			}
		}

		$this->doctrine->em->flush();
		return render_json(["success"=>true]);

	}

}

/* End of file  */
/* Location: ./application/controllers/ */