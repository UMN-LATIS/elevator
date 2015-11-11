<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Asset_model extends CI_Model {

	/**
	 * these are items that every template will have
	 * @var array of strings
	 */
	private $objectId;

	/**
	 * These are the values that are valid for all items, regardless of metadata schema.  In a relational database, these would be columns.
	 */
	public $globalValues = ["templateId"=>"", "readyForDisplay"=>"", "collectionId"=>"",  "availableAfter"=>"", "modified"=>"", "modifiedBy"=>"", "createdBy"=>"", "cachedUploadCount"=>0, "cachedLocationData"=>null, "cachedDateData"=>null, "cachedPrimaryFileHandler"=>null, "collectionMigration"=>null];

	public $assetTemplate = null;
	public $assetObjects = array();
	public $templateId = null;
	public $forceCollection = false;

	public function __construct($objectId=null)
	{
		parent::__construct();
		if(!is_null($objectId)) {
			$this->loadAssetById($objectId);
		}
	}

	/**
	 * special handy getter for objectId
	 */
	public function getObjectId() {
		return $this->objectId;
	}

	public function getGlobalValue($globalValue) {
		if(isset($this->globalValues[$globalValue])) {
			return $this->globalValues[$globalValue];
		}
		else {
			return NULL;
		}
	}

	public function setGlobalValue($key, $value) {
		if(array_key_exists($key, $this->globalValues)) {
			$this->globalValues[$key] = $value;
 		}
	}


	/**
	 * if we've got a full mongo record, we can instantiate an object from that without having to
	 * go back to the DB.
	 */
	public function loadAssetFromRecord($record) {

		$this->objectId = (string)$record["_id"];

		$this->templateId = $record['templateId'];

		if($this->loadDataFromObject($record)) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}


	public function loadAssetById($objectId) {

		try {
			$mongoId = new MongoId($objectId);
		}
		catch (MongoException $ex) {
			return FALSE;
		}
		$this->objectId = $objectId;

		$asset = $this->qb->where(['_id'=>$mongoId])->getOne($this->config->item('mongoCollection'));

		if(!isset($asset)) {
			return FALSE;
		}


		$this->loadAssetFromRecord($asset);

		return TRUE;
	}

	/**
	 * @param  [type]  $type           The type of asset (using widget class) that you want to find
	 * @param  [type]  $asset          The target asset (null for current asset)
	 * @param  integer $recursionDepth How far down the rabbit hole shoudl we search (via related_asset widgets)
	 * @return [array]                  Array of widgets
	 */
	public function getAllWithinAsset($type,$asset=null, $recursionDepth=0) {

		if($asset == null) {
			$asset = $this;
		}
		$widgetArray = array();
		if(!isset($asset->assetObjects) || count($asset->assetObjects) == 0) {
			return array();
		}

		foreach($asset->assetObjects as $widget) {
			if(get_class($widget) == $type) {
				$widgetArray[] = $widget;
			}
		}

		if($recursionDepth >0) {
			$relatedAssets = $this->getAllWithinAsset("Related_asset", $this, 0);
			foreach($relatedAssets as $relatedAsset) {
				foreach($relatedAsset->fieldContentsArray as $entry) {
					$widgetArray = array_merge($widgetArray, $this->getAllWithinAsset($type, $entry->getRelatedAsset(), $recursionDepth-1));

				}
			}
		}

		return $widgetArray;
	}

	/**
	 * this should just be one line with doctrine to get as scalar array, but I can't figure it out right now
	 * @return [type] [description]
	 */
	public function getDrawers()
	{

		$drawerList =  $this->doctrine->em->getRepository("Entity\DrawerItem")->findBy(['asset' => $this->getObjectId(), 'excerptAsset'=>NULL]);
		$drawerArray = array();
		foreach($drawerList as $drawer) {
			$drawerArray[] = $drawer->getDrawer()->getId();
		}
		return $drawerArray;

	}

	/**
	 * Fallback for cases in which we can't figure out a primary
	 */
	private function getFirstWithinAsset($asset, $type) {
		$widgetArray = $this->getAllWithinAsset($type,$asset,1);
		if(count($widgetArray)>0) {
			foreach($widgetArray as $widget) {
				foreach($widget->fieldContentsArray as $fieldContents) {
					return $fieldContents;
				}
			}

		}
		return FALSE;

	}

	/**
	 * Find the primary
	 * @param  [type] $asset [description]
	 * @param  [type] $type  [description]
	 * @return [type]        [description]
	 */
	private function findPrimaryWithinAsset($asset, $type) {

		$widgetArray = $this->getAllWithinAsset($type,$asset,0);
		if(count($widgetArray)>0) {
			foreach($widgetArray as $widget) {
				foreach($widget->fieldContentsArray as $fieldContents) {
					if((!$widget->getAllowMultiple() || count($widget->fieldContentsArray)==1) || $fieldContents->isPrimary) {
						return $fieldContents;
					}
				}
			}
		}

		$widgetArray = $this->getAllWithinAsset($type,$asset,1);
		if(count($widgetArray)>0) {
			foreach($widgetArray as $widget) {
				foreach($widget->fieldContentsArray as $fieldContents) {
					if((!$widget->getAllowMultiple() || count($widget->fieldContentsArray)==1) || $fieldContents->isPrimary) {
						return $fieldContents;
					}
				}
			}
		}


		/**
		 * This template doesn't have any upload widgets, return false
		 */

		return FALSE;
	}

	/**
	 * Find "the" preview for this asset - if we have an upload widget attached, get the primary item there.  If we don't, search all
	 * our nested assets to find the most primary (we may have multiple equally ranked ones, if so, pick one and hope the user doesn't
	 * complain too much.)
	 * @return [type] [description]
	 */
	public function getPrimaryFilehandler() {

		$contents = null;
		$foundPrimary = FALSE;
		if(!$contents = $this->findPrimaryWithinAsset($this, "Upload")) {
			// no first tier primary, try nested - first see if the primary related has an image.
			$relatedArray = $this->getAllWithinAsset("Related_asset", $this);
			foreach($relatedArray as $asset) {
				foreach($asset->fieldContentsArray as $fieldContents) {
					if(!$asset->getAllowMultiple() || $fieldContents->isPrimary) {
						$contents = $this->findPrimaryWithinAsset($fieldContents->getRelatedAsset(), "Upload");
						if(!$contents) {
							$contents = $this->getFirstWithinAsset($fieldContents->getRelatedAsset(), "Upload");
						}
						else {
							$foundPrimary = true;
							break;
						}
					}
				}
				if($foundPrimary) {
					break;
				}
			}

			if(!$contents) {
				$contents = $this->getFirstWithinAsset($this, "Upload");
			}
		}




		if($contents) {
			if(!isset($contents->fileHandler)) {
				throw new Exception("no file handler attached");
				return null;
			}
			$fileHandler = $contents->fileHandler;
			if($fileHandler) {

				return $fileHandler;
			}
			else {
				throw new Exception('Primary File Handler Not Found');
				return NULL;
			}
		}
		throw new Exception('No File Handlers Found');
		return NULL;
	}

	/**
	 * Build an object from a json representation.
	 * This may be coming from the browser, or from mongo.
	 * @param  [type] $jsonData [description]
	 * @return [type]           [description]
	 */
	public function loadDataFromObject($jsonData) {
		if(!$this->templateId) {
			return false;
		}
		# get the template for this asset, it contains the widgets
		$this->assetTemplate = $this->asset_template->getTemplate($this->templateId);
		$populatedWidgetArray = array();
		# go through all the widgets from the template, see if they're set in the jsonData
		foreach($this->assetTemplate->widgetArray as $widget) {
			$widgetKey = $widget->getFieldTitle();

			if(isset($jsonData[$widgetKey])) {
				$populatedWidgetArray[$widgetKey] = clone $widget;
				$populatedWidgetArray[$widgetKey]->parentObjectId = $this->getObjectId();
				if(is_array($jsonData[$widgetKey])) {
					$i=0;
					$stamp = microtime(true);
					foreach($jsonData[$widgetKey] as $key=>$value) {
						if(is_array($value)) {
							$tempObject = $widget->getContentContainer();
							$tempObject->parentObjectId = $this->getObjectId();
							$tempObject->loadContentFromArray($value);
							if(isset($jsonData[$widgetKey]['isPrimary'])) {
								/**
							      * this case occurs on form submission, as isPrimary is widget level, not content level.
							      * But we want to track it content-level otherwise.
							      * technically we don't need to set the false, as false will be the default on loads from
							      * mongo - we won't end up with duplicate isPrimaries.  But this seemed like the smart way to go,
							      * just in case.
								  */
								if($jsonData[$widgetKey]['isPrimary'] == $i) {
									$tempObject->isPrimary = true;
								}
								else {
									$tempObject->isPrimary = false;
								}
							}
							$populatedWidgetArray[$widgetKey]->addContent($tempObject);
							$i++;
						}

					}

				}
			}
		}

		$this->assetObjects = $populatedWidgetArray;

		/**
		 * Generally we want to access these in display order
		 */
		$this->sortBy('viewOrder');

		foreach($this->globalValues as $key=>$value) {
			if(isset($jsonData[$key])) {
				$this->globalValues[$key] = $jsonData[$key];
				if($jsonData[$key] === "on") { // deal with checkboxes for global values from the browser
					$this->globalValues[$key] = true;
				}
			}
			else {
				$this->globalValues[$key] = null;
			}
		}

		if(is_string($this->globalValues["availableAfter"])) {
			date_default_timezone_set('UTC');
			$this->globalValues["availableAfter"] = new MongoDate(strtotime($this->globalValues["availableAfter"]));
		}

		return TRUE;
	}


	/**
	 * serialize the asset as an array.  This array should contain all the data necessary to reconstruct
	 * this asset (this is what's stored in the DB)
	 */

	function getAsArray($nestedDepth=false, $useTemplateTitles=false) {
		$outputObject = array();
		foreach($this->assetObjects as $assetKey=>$assetObject) {
			if($assetObject->hasContents()) {
				if($useTemplateTitles) {
					$outputKey = $this->assetTemplate->widgetArray[$assetKey]->getLabel();
				}
				else {
					$outputKey = $assetKey;
				}

				$outputObject[$outputKey] = $assetObject->getAsArray($nestedDepth);
			}
		}

		foreach($this->globalValues as $key=>$value) {
			if($key == "templateId" || $key == "collectionId") {
				$outputObject[$key] = (int)$value;
			}
			else {
				$outputObject[$key] = $value;
			}

		}
		return $outputObject;
	}

	function getAsText($nestedDepth=false) {
		$outputObject = array();
		foreach($this->assetObjects as $assetKey=>$assetObject) {
			if($assetObject->getSearchable() && $assetObject->hasContents()) {
				$outputObject[$assetKey] = $assetObject->getAsText($nestedDepth);
			}
		}

		foreach($this->globalValues as $key=>$value) {
			$outputObject[$key] = $value;
		}

		return $outputObject;
	}

	/**
	 * very similar to getAsText, but doesn't guarantee pure text - some entries may be arrays
	 */
	function getSearchEntry($nestedDepth=false) {
		$outputObject = array();
		foreach($this->assetObjects as $assetKey=>$assetObject) {
			if($assetObject->getSearchable() && $assetObject->hasContents()) {
				$outputObject[$assetKey] = $assetObject->getSearchEntry($nestedDepth);
			}
		}

		foreach($this->globalValues as $key=>$value) {
			$outputObject[$key] = $value;
		}

		return $outputObject;
	}


	// Build the title for the object.
	//
	// A title is essentially the first item in the template that's set to display in preview.
	//
	// If that happens to be a related asset, we'll descend and get its title.
	//
	// This can either return an array (in case the first asset has mutliple values) or a comma seperated string
	//
	public function getAssetTitle($collapse=false) {
		$this->sortBy('viewOrder');
		$outputObject = array();
		$foundFirst = false;
		foreach($this->assetObjects as $assetKey=>$assetObject) {
			$assetObject->primarySort();
			if($assetObject->getDisplayInPreview()) {
				if(get_class($assetObject) == "Related_asset") {
					$assetTitle = array();
					foreach($assetObject->fieldContentsArray as $entry) {
						array_push($assetTitle, $entry->getRelatedAsset()->getAssetTitle(true));
					}

				}
				else {
					$assetTitle = $assetObject->getArrayOfText(false);
				}

				if($collapse) {
					return join(", ", $assetTitle);
				}
				else {
					return $assetTitle;
				}
			}
		}
		if($collapse) {
			return "";
		}
		else {
			return array();
		}

	}

	/**
	 * Generate an array representation of our search result preview
	 * This only includes the fields that are flagged (in the widget) as displayInPreview
	 * We also bake in location and date date, as long as those fields are flagged as available for public consumption.
	 * This array is eventually passed back to the browser as json and is further manipulated from here.
	 * We include the type of object (flagged true) to deal with the way Handlebars does conditionals
	 * @return [type] [description]
	 */
	public function getSearchResultEntry() {
		$this->sortBy('viewOrder');
		$outputObject = array();
		$foundFirst = false;
		$uploadCount = 0;
		$outputObject['title'] = array_shift($this->getAssetTitle()); // only show the first title
		foreach($this->assetObjects as $assetKey=>$assetObject) {
			if($assetObject->getDisplayInPreview() && get_class($assetObject) != "Upload") {
				$entryAsText = $assetObject->getArrayOfText(false);

				if(array_shift($entryAsText) != $outputObject['title'] && $assetObject->hasContents()) {
					/**
					 * ok, I really really don't want to have a special case here.  Ok?
					 * but I also don't want to fork the widget API.
					 */

					if(get_class($assetObject) == "Related_asset") {
						$titleArray = array();
						foreach($assetObject->fieldContentsArray as $entry) {
							$titleArray[] = $entry->getRelatedAsset()->getAssetTitle();
							foreach($entry->getRelatedAsset()->getAllWithinAsset("Upload") as $uploadWidget) {
								$uploadCount += count($uploadWidget->fieldContentsArray);
							}
						}

						if($outputObject['title'] == reset($titleArray[0])) {
							continue;
						}
						foreach($titleArray as $key=>$value) {
							$titleArray[$key] = join(", ", $value);
						}

						$outputObject['entries'][] = ["label"=>$assetObject->getLabel(), get_class($assetObject)=>true, "entries"=>$titleArray];
					}
					else {
						$outputObject['entries'][] = ["label"=>$assetObject->getLabel(), get_class($assetObject)=>true, "entries"=>$assetObject->getArrayOfText(false)];
					}


				}
			}
			elseif(get_class($assetObject) == "Upload") {
				$uploadCount += count($assetObject->fieldContentsArray);
			}

		}

		/**
		 * We've cached the upload count for nested elements, so we can use that to draw the little eyeball, in
		 * addition to any display elements or first level uploads.
		 */
		if($this->getGlobalValue("cachedUploadCount")) {
			$uploadCount += $this->getGlobalValue("cachedUploadCount");
		}


		if($uploadCount > 1) {
			$outputObject["fileAssets"] = $uploadCount;
		}



		if($this->getGlobalValue("cachedLocationData")) {
			$outputObject['locations'] = $this->getGlobalValue('cachedLocationData');
		}

		if($this->getGlobalValue("cachedDateData")) {
			$outputObject['dates'] = $this->getGlobalValue('cachedDateData');
		}



		$uploadAssets = $this->getAllWithinAsset("Upload", $this, 0);
		foreach($uploadAssets as $upload) {
			if($upload->getDisplay()) {

				$entryArray = array();
				$dateArray = array();
				foreach($upload->fieldContentsArray as $fieldContent) {
					$fieldContent->extractLocation = $upload->extractLocation; // this is wrong, should use a method on the widget
					$fieldContent->extractDate = $upload->extractDate;

					if($fieldContent->locationData) {
						$entryArray[] = $fieldContent->getAsArray(false);

					}
					if($fieldContent->dateData) {
						$dateArray[] = $fieldContent->getAsArray(false);
					}
				}
				if(count($entryArray)>0) {
					$outputObject['locations'][] = ["label"=>$upload->getLabel(), "entries"=>$entryArray];
				}
				if(count($dateArray)> 0) {
					$outputObject['dates'][] = ["label"=>$upload->getLabel(), "dateAsset"=>$dateArray];
				}

			}
		}

		$outputObject["objectId"] = $this->objectId;

		try {
			$fileHandler = false;
			if($fileHandlerId = $this->getGlobalValue("cachedPrimaryFileHandler")) {
				$fileHandler = $this->filehandler_router->getHandlerForObject($fileHandlerId);
				if($fileHandler) {
					$fileHandler->loadByObjectId($fileHandlerId);
				}
			}

			if(!$fileHandler) {
				$fileHandler = $this->getPrimaryFilehandler();
			}
			$outputObject["primaryHandlerType"] = get_class($fileHandler);
			$outputObject["primaryHandlerId"] = $fileHandler->getObjectId();
			$outputObject["primaryHandlerThumbnail"] = $fileHandler->getPreviewThumbnail()->getURLForFile(true);
			$outputObject["primaryHandlerThumbnail2x"] = $fileHandler->getPreviewThumbnail(true)->getURLForFile(true);
			$outputObject["primaryHandlerTiny"] = $fileHandler->getPreviewTiny()->getURLForFile(true);
			$outputObject["primaryHandlerTiny2x"] = $fileHandler->getPreviewTiny(true)->getURLForFile(true);
		}
		catch (Exception $e) {
			// don't need to do anything, might not have a handler, that's ok.
		}


		return $outputObject;

	}


	/**
	 * save the asset, using the internal representation we've already built.  Return the objectId.
	 * In case we already have an objectId, snapshot the existing mongo record, save it to our history, then replace it
	 * parentarray is the ids of the parent in this chain so we don't recurse
	 * @return [type] [description]
	 */
	public function save($reindex=true) {
		$arrayData = $this->getAsArray();

		$arrayData['modified'] = new \MongoDate();

		if($this->user_model && isset($this->user_model->user)) {
			$arrayData['modifiedBy'] = $this->user_model->getId();
			if(!isset($arrayData['createdBy'])) {
				$arrayData['createdBy'] = $this->user_model->getId();
			}
		}
		else {
			$arrayData['modifiedBy'] = 0;
		}

		/**
		 * cache the nested asset upload objects (whew) so that we can drag the little eyeball on search results
		 * without having to walk all of these.
		 * This only counts things that won't otherwise be used to compute the search result view.
		 * Someday, we can cache all of that, and then we'll be awesome.
		 */
		$cachedUploadCount = 0;
		foreach($this->assetObjects as $assetObject) {
			if(get_class($assetObject) == "Related_asset" && !$assetObject->getDisplayInPreview()) {
				foreach($assetObject->fieldContentsArray as $entry) {
					foreach($entry->getRelatedAsset()->getAllWithinAsset("Upload") as $uploadWidget) {
						$cachedUploadCount += count($uploadWidget->fieldContentsArray);
					}
				}
			}
		}

		$arrayData['cachedUploadCount'] = $cachedUploadCount;

		try {
			$primaryFileHandler = $this->getPrimaryFilehandler();
			if($primaryFileHandler) {
				$arrayData['cachedPrimaryFileHandler'] = $primaryFileHandler->getObjectId();
			}
		}
		catch (Exception $e) {
			// eh, this is ok.
		}

		/**
		 * populate with cached location data (flattened, one layer deep) for drawing maps
		 */
		$locationAssets = $this->getAllWithinAsset("Location", $this, 1);
		$locationArray = array();
		foreach($locationAssets as $location) {
			if($location->getDisplay()) {
				$locationArray[] = ["label"=>$location->getLabel(), "entries"=>$location->getAsArray(false)];
			}
		}

		$arrayData['cachedLocationData'] = $locationArray;



		/**
		 * populate with date data (flattened, one layer deep) for drawing timeline
		 */
		$dateAssets = $this->getAllWithinAsset("Date",$this, 1);
		$dateArray = array();
		foreach($dateAssets as $dateAsset) {
			if($dateAsset->getDisplay()) {
				$dateArray[] = ["label"=>$dateAsset->getLabel(), "dateAsset"=>$dateAsset->getAsArray(false)];
			}
		}
		$arrayData['cachedDateData'] = $dateArray;

		if(!isset($this->objectId)) {
	   		$this->objectId = (string)$this->qb->save($this->config->item('mongoCollection'), $arrayData);
    	}
        else {
        	$oldAsset = new Asset_model;
        	$oldAsset->loadAssetById($this->objectId);


        	/**
        	 * if collection is changing, we need to check and see if we're changing buckets.
        	 * if so, we don't change the bucket right away, but rather set a flag for that and start a background process.
        	 * background process sets special forceCollection falg
        	 */

        	if($oldAsset->getGlobalValue("collectionId") != $this->getGlobalValue("collectionId") && !$this->forceCollection) {
        		$oldCollection = $this->collection_model->getCollection($oldAsset->getGlobalValue("collectionId"));
        		$newCollection = $this->collection_model->getCollection($this->getGlobalValue("collectionId"));


        		if($oldCollection && $newCollection && $oldCollection->getBucket() != $newCollection->getBucket()) {
        			$arrayData["collectionId"] = $oldAsset->getGlobalValue("collectionId");
        			$arrayData["collectionMigration"] = true;

					$pheanstalk = new Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));
					$newTask = json_encode(["objectId"=>$this->objectId,"instance"=>$this->instance->getId(), "targetCollection"=>$this->getGlobalValue("collectionId")]);
					$jobId= $pheanstalk->useTube('collectionMigration')->put($newTask, NULL, 1);

        		}

        	}

        	$oldAssetArray = $oldAsset->getAsArray();
        	$oldAssetArray["sourceId"] = new MongoId($this->objectId);

        	$history = $this->qb->insert($this->config->item('historyCollection'), $oldAssetArray);
        	unset($oldAssetArray);
        	unset($oldAsset);
        	$arrayData["_id"] = new MongoId($this->objectId);
        	$objectId = (string)$this->qb->save($this->config->item('mongoCollection'), $arrayData);
        	$this->objectId = $objectId;
        	unset($arrayData);
        	// TODO: potentially get upload objects and set objectId on them so they reference back to us?
        	// this is hacky.
    	}


		// if this asset isn't supposed to be available, don't add it to the index
		// TODO: also check template
		$noIndex=false;
		if($this->getGlobalValue('availableAfter')) {
			date_default_timezone_set('UTC');
			$afterDate = strtotime($this->getGlobalValue('availableAfter'));
			if($afterDate > time()) {
				$noIndex=true;
			}
		}

		if($reindex && !$noIndex) {
			$pheanstalk = new Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));
			$newTask = json_encode(["objectId"=>$this->objectId,"instance"=>$this->instance->getId()]);
			$jobId= $pheanstalk->useTube('reindex')->put($newTask, NULL, 1);
		}

    	return $this->objectId;
	}

	// reindex self and children, preventing recursion

	public function reindex($parentArray=array()) {


		$this->load->model("search_model");

		if(count($parentArray) === 0) {
			$parentArray[] = $this->objectId;
		}

		if(count($parentArray)>5 ) {
			return;
		}

		$this->search_model->addOrUpdate($this);

		// now find any related items and resave them.
		//
		// we build a parent array so we don't recurse


		$results = $this->search_model->find(["searchText"=>$this->objectId, "searchRelated"=>true], false);
		$parentArray[] = $this->objectId;

		foreach($results["searchResults"] as $result) {
			if(!in_array($result, $parentArray)) {
				$this->logging->logError("updating", $result);
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetById($result);
				$tempAsset->save(false);
				$tempAsset->reindex($parentArray);
			}
		}

	}

	// TODO: should this really be within this class?
	public function getHandlerForId($fileId) {
		$uploadAssets = $this->getAllWithinAsset("Upload");

		foreach($uploadAssets as $uploadAsset) {
			foreach($uploadAsset->fieldContentsArray as $uploadAsset) {
				if(isset($uploadAsset->fileHandler) && $uploadAsset->fileHandler->getObjectId() == $fileId) {
					return $uploadAsset->fileHandler;
				}
			}
		}
		return NULL;
	}

	/**
	 * Re-sort the widget array by one of the two sort values we store
	 * @param  [type] $sortType [description]
	 * @return [type]           [description]
	 */
	public function sortBy($sortType) {
		if($sortType == "templateOrder") {
   			uasort($this->assetObjects, function($a, $b) {
    				return $a->getTemplateOrder() > $b->getTemplateOrder() ? 1 : -1;
    		});
   		}
   		elseif($sortType == "viewOrder") {
   			uasort($this->assetObjects, function($a, $b) {
    				return $a->getViewOrder() > $b->getViewOrder() ? 1 : -1;
    		});
   		}
	}

	public function delete()
	{
		$files = $this->getAllWithinAsset("Upload");
		foreach($files as $file) {
			foreach($file->fieldContentsArray as $entry) {
				$entry->fileHandler->deleteFile();
			}
		}


		$oldAsset = new Asset_model;
        $oldAsset->loadAssetById($this->objectId);
        $oldAssetArray = $oldAsset->getAsArray();
        $oldAssetArray["sourceId"] = new MongoId($this->objectId);
        $oldAssetArray["deleted"] = true;
        $oldAssetArray["deletedDate"] = new \MongoDate();

        $objectId = $this->qb->insert($this->config->item('historyCollection'), $oldAssetArray);
		$this->qb->where(['_id'=>new MongoId($this->getObjectId())])->delete($this->config->item('mongoCollection'));
	}

}

/* End of file asset.php */
/* Location: ./application/models/asset.php */
