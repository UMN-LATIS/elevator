<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Asset_model extends CI_Model {

	/**
	 * these are items that every template will have
	 * @var array of strings
	 */
	private $objectId;
	public $objectCache = array();
	public $useObjectCaching = false;
	/**
	 * These are the values that are valid for all items, regardless of metadata schema.  In a relational database, these would be columns.
	 */
	public $globalValues = ["templateId"=>"", "readyForDisplay"=>"", "collectionId"=>"",  "availableAfter"=>"", "modified"=>"", "modifiedBy"=>"", "createdBy"=>"","collectionMigration"=>null, "deleted"=>false];

	public $assetTemplate = null;
	public $assetObjects = array();
	public $templateId = null;
	public $forceCollection = false;
	public $assetObject = null;

	// if necessary, shoudl we use stale caches?
	public $useStaleCaches = TRUE;
	public $hydrated = false;

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
		if(isset($this->assetObject)) {
			return $this->assetObject->getAssetId();
		}
		else {
			return false;
		}
	}

	public function getIndexId() {
		if(isset($this->assetObject)) {
			return $this->assetObject->getId();
		}
		else {
			return false;
		}
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
	 * if we've got a full postgres record, we can instantiate an object from that without having to
	 * go back to the DB.
	 */
	public function loadAssetFromRecord($record=null, $noHydrate = false) {
		if(!$record && !$this->assetObject) {
			return;
		}

		if($record) {
			$this->assetObject = $record;	
		}


		$this->templateId = $record->getTemplateId();
		$this->setGlobalValue("readyForDisplay", $record->getReadyForDisplay());
		$this->setGlobalValue("templateId", $record->getTemplateId());
		$this->setGlobalValue("collectionId", $record->getCollectionId());
		$this->setGlobalValue("availableAfter", $record->getAvailableAfter());
		$this->setGlobalValue("modified", $record->getModifiedAt());
		$this->setGlobalValue("modifiedBy", $record->getModifiedBy());
		$this->setGlobalValue("deleted", $record->getDeleted());

		if($noHydrate) {
			return;
		}

		if($this->loadWidgetsFromArray($record->getWidgets())) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}

	public function getCachedAsset($objectId) {
		if($this->asset_model->useObjectCaching == true) {
			if(array_key_exists($objectId, $this->asset_model->objectCache)) {
				return $this->asset_model->objectCache[$objectId];
			}

		}

		return FALSE;
	}

	public function loadAssetById($objectId, $noHydrate = false) {
		$asset = $this->doctrine->em->getRepository('Entity\Asset')->findOneBy(["assetId"=>$objectId]);

		if(!isset($asset)) {
			return FALSE;
		}
		$this->assetObject = $asset;

		if($noHydrate) {
			// dont actually hydrate the object
			return true;
		}
		$this->hydrated = true;

		if($this->loadAssetFromRecord($asset)) {
			if($this->asset_model->useObjectCaching == true) {
				$this->asset_model->objectCache[$objectId] = $this;
			}	
			return true;
		}
		return false;
	}


	/**
	 * Methods for working with our internal object cache.
	 * The idea is that on read-only operations, we should make sure we don't load the same object repeatedly
	 * (for example, an asset with nested records)
	 */

	public function enableObjectCache() {
		$this->asset_model->useObjectCaching = true;
	}

	public function disableObjectCache() {
		$this->asset_model->useObjectCaching = false;
		$this->asset_model->objectCache = array();
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
			if(get_class($widget) == $type && $widget->hasContents()) {
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
	public function getDrawers($includeExcerpts=false)
	{

		$matchArray = ["asset"=>$this->getObjectId()];
		if(!$includeExcerpts) {
			$matchArray["excerptAsset"] = null;
		}
		$drawerList =  $this->doctrine->em->getRepository("Entity\DrawerItem")->findBy($matchArray);
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

		// $widgetArray = $this->getAllWithinAsset($type,$asset,1);

		// if(count($widgetArray)>0) {
		// 	foreach($widgetArray as $widget) {
		// 		foreach($widget->fieldContentsArray as $fieldContents) {
		// 			if((!$widget->getAllowMultiple() || count($widget->fieldContentsArray)==1) || $fieldContents->isPrimary) {
		// 				return $fieldContents;
		// 			}
		// 		}
		// 	}
		// }



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
	public function getPrimaryFilehandler($tryCache = true) {
		$fileHandler = NULL;

		if($tryCache && $this->assetObject->getAssetCache() && ($this->useStaleCaches || !$this->assetObject->getAssetCache()->getNeedsRebuild())) {
			$fileHandler = $this->filehandler_router->getHandledObject($this->assetObject->getAssetCache()->getPrimaryHandlerCache());
		}

		if(!$fileHandler) {
			$fileHandler = null;
			$foundPrimary = FALSE;
			if(!$uploadContents = $this->findPrimaryWithinAsset($this, "Upload")) {
				// no first tier primary, try nested - first see if the primary related has an image.
				$relatedArray = $this->getAllWithinAsset("Related_asset", $this);
				foreach($relatedArray as $asset) {

					foreach($asset->fieldContentsArray as $fieldContents) {
						if(!$asset->getAllowMultiple() || $fieldContents->isPrimary || count($asset->fieldContentsArray)==1) {
							try {
								$fileHandler = $fieldContents->getPrimaryFilehandler();
							}
							catch (Exception $e) {

							}

							if($fileHandler) {
								$foundPrimary = true;
								break;
							}
						}
					}
					if($foundPrimary) {
						break;
					}
				}

				if(!$fileHandler) {
					$contents = $this->getFirstWithinAsset($this, "Upload");
					if($contents) {
						$fileHandler = $contents->getFileHandler();
					}
				}
			}
			else {
				if($uploadContents->getFileHandler()) {
					$fileHandler = $uploadContents->getFileHandler();
				}
			}

			if(!$fileHandler) {
				throw new Exception("no file handler attached");
				return null;
			}
		}


		if($fileHandler) {
			return $fileHandler;
		}
		else {
			throw new Exception('Primary File Handler Not Found');
			return NULL;
		}
		return NULL;
	}

	public function createObjectFromJSON($json) {
		if(!isset($this->assetObject)) {
			$this->assetObject = new Entity\Asset;
		}

		$this->templateId = $json['templateId'];


		foreach($this->globalValues as $key=>$value) {
			if(isset($json[$key])) {
				$this->globalValues[$key] = $json[$key];
				if($json[$key] === "on") { // deal with checkboxes for global values from the browser
					$this->globalValues[$key] = true;
				}
			}
			else {
				$this->globalValues[$key] = null;
			}
		}


		if(is_string($this->globalValues["availableAfter"]) && strlen($this->globalValues["availableAfter"])>5) {
			date_default_timezone_set('UTC');
			$this->globalValues["availableAfter"] = new DateTime($this->globalValues["availableAfter"]);
		}

		$this->loadWidgetsFromArray($json);

	}


	/**
	 * Build an object from a json representation.
	 * This may be coming from the browser, or from mongo.
	 * @param  [type] $jsonData [description]
	 * @return [type]           [description]
	 */
	public function loadWidgetsFromArray($jsonData) {
		// echo json_encode($jsonData);
		// die;
		if(!$this->templateId) {
			return false;
		}
		# get the template for this asset, it contains the widgets
		$this->assetTemplate = $this->asset_template->getTemplate($this->templateId);
		if(!$this->assetTemplate) {
			return false;
		}
		$populatedWidgetArray = array();

		# go through all the widgets from the template, see if they're set in the jsonData
		foreach($this->assetTemplate->widgetArray as $widget) {
			$widgetKey = $widget->getFieldTitle();

			if(isset($jsonData[$widgetKey])) {
				$populatedWidgetArray[$widgetKey] = clone $widget;
				$populatedWidgetArray[$widgetKey]->parentObjectId = $this->getObjectId();
				if(is_array($jsonData[$widgetKey])) {
					$i=0;
					foreach($jsonData[$widgetKey] as $key=>$value) {
						if(is_array($value)) {
							$tempObject = $widget->getContentContainer();
							$tempObject->parentObjectId = $this->getObjectId();
							$tempObject->parentObject = $this;
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

							// only save this widget if it actually has contents.  This assumes widgets are well behaved.
							if($tempObject->hasContents()) {
								$populatedWidgetArray[$widgetKey]->addContent($tempObject);
							}

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


		return TRUE;
	}


	public function getWidgetArray($nestedDepth=0, $useTemplateTitles=false) {

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
		return $outputObject;
	}

	/**
	 * serialize the asset as an array.  This array should contain all the data necessary to reconstruct
	 * this asset (this is what's stored in the DB)
	 */

	function getAsArray($nestedDepth=0, $useTemplateTitles=false, $includeRelatedAssetCache = false) {
		$outputObject = $this->getWidgetArray($nestedDepth, $useTemplateTitles);

		foreach($this->globalValues as $key=>$value) {
			if($key == "templateId" || $key == "collectionId") {
				$outputObject[$key] = (int)$value;
			}
			else {
				$outputObject[$key] = $value;
			}

		}

		// inline the related asset cache so consumers can draw thumbnails
		if($includeRelatedAssetCache) {
			$assetCache = $this->assetObject->getAssetCache();
			$outputObject['relatedAssetCache'] = $assetCache->getRelatedAssetCache();
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
		
		$assetObject = $this->getAssetTitleWidget();

		if(!$assetObject) {
			if($collapse) {
				return "";
			}
			else {
				return array();
			}
		}

		if(get_class($assetObject) == "Related_asset") {
			$assetTitle = array();
			foreach($assetObject->fieldContentsArray as $entry) {
				$titleArray = $entry->getRelatedObjectTitle(true);
				array_push($assetTitle, $titleArray);
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

	public function getAssetTitleWidget() {
		$this->sortBy('viewOrder');
		foreach($this->assetObjects as $assetKey=>$assetObject) {
			$assetObject->primarySort();
			if($assetObject->getDisplayInPreview()) {
				return $assetObject;
			}
		}
		
		return false;

	}

	/**
	 * Generate an array representation of our search result preview
	 * This only includes the fields that are flagged (in the widget) as displayInPreview
	 * We also bake in location and date date, as long as those fields are flagged as available for public consumption.
	 * This array is eventually passed back to the browser as json and is further manipulated from here.
	 * We include the type of object (flagged true) to deal with the way Handlebars does conditionals
	 * @return [type] [description]
	 */

	public function buildSearchResultEntry() {
		$this->sortBy('viewOrder');
		$outputObject = array();
		$foundFirst = false;
		$uploadCount = 0;
		$rootTitleArray = $this->getAssetTitle();
		$outputObject['title'] = array_shift($rootTitleArray); // only show the first title
		foreach($this->assetObjects as $assetKey=>$assetObject) {
			if($assetObject->getDisplayInPreview() && get_class($assetObject) != "Upload") {

				// check to make sure this isn't the same value we used as the title
				// we'll handle it for related_asset later, for better perf
				if(get_class($assetObject) !== "Related_asset") {
					$entryAsText = $assetObject->getArrayOfText(false);
				}
				else {
					$entryAsText = array();
				}

				if(array_shift($entryAsText) != $outputObject['title'] && $assetObject->hasContents()) {
					/**
					 * ok, I really really don't want to have a special case here.  Ok?
					 * but I also don't want to fork the widget API.
					 */

					if(get_class($assetObject) == "Related_asset") {
						$titleArray = array();
						$maxCount = 3;
						$count = 0;
						foreach($assetObject->fieldContentsArray as $entry) {
							if($count > $maxCount) {
								break;
							}
							$titleArray[] = $entry->getRelatedObjectTitle();
							$count++;
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

		}

		/**
		 * We've cached the upload count for nested elements, so we can use that to draw the little eyeball, in
		 * addition to any display elements or first level uploads.
		 */
		$nestedUploadCount = 0;
		foreach($this->assetObjects as $assetObject) {
			if(get_class($assetObject) == "Related_asset") {
				foreach($assetObject->fieldContentsArray as $entry) {
					if(!$relatedAsset = $entry->getRelatedAsset()) {
						continue;
					}
					foreach($relatedAsset->getAllWithinAsset("Upload") as $uploadWidget) {
						$nestedUploadCount += count($uploadWidget->fieldContentsArray);
					}
				}
			}
			elseif(get_class($assetObject) == "Upload") {
				$nestedUploadCount += count($assetObject->fieldContentsArray);
			}
		}

		$uploadCount += $nestedUploadCount;
		if($uploadCount > 1) {
			$outputObject["fileAssets"] = $uploadCount;
		}

		$locationAssets = $this->getAllWithinAsset("Location", $this, 1);
		$locationArray = array();
		foreach($locationAssets as $location) {
			if($location->getDisplay()) {
				$locationArray[] = ["label"=>$location->getLabel(), "entries"=>$location->getAsArray(false)];
			}
		}
		$outputObject['locations'] = $locationArray;

		$dateAssets = $this->getAllWithinAsset("Date",$this, 1);
		$dateArray = array();
		foreach($dateAssets as $dateAsset) {
			if($dateAsset->getDisplay()) {
				$dateArray[] = ["label"=>$dateAsset->getLabel(), "dateAsset"=>$dateAsset->getAsArray(false)];
			}
		}
		$outputObject['dates'] = $dateArray;


		$uploadAssets = $this->getAllWithinAsset("Upload", $this, 0);
		foreach($uploadAssets as $upload) {
			if($upload->getDisplay()) {

				$entryArray = array();
				$dateArray = array();
				$limit = 0;
				foreach($upload->fieldContentsArray as $fieldContent) {
					if($limit > 10) {
						break;
					}
					$limit++;

					$fieldContent->extractLocation = $upload->extractLocation; // this is wrong, should use a method on the widget
					$fieldContent->extractDate = $upload->extractDate;

					if($fieldContent->getLocationData()) {
						$entryArray[] = $fieldContent->getAsArray(false);

					}
					if($fieldContent->getDateData()) {
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

		$outputObject["objectId"] = $this->getObjectId();

		try {
			$fileHandler = false;

			$fileHandler = $this->getPrimaryFilehandler();
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


	public function getSearchResultEntry() {
		if($assetCache = $this->assetObject->getAssetCache()) {
			if(!$assetCache->getNeedsRebuild()) {
				return $assetCache->getSearchResultCache();
			}
		}
		if(!$this->hydrated) {
			// make sure to hydra
			$this->loadAssetById($this->getObjectId());
		}

		return $this->buildSearchResultEntry();
	}


	/**
	 * save the asset, using the internal representation we've already built.  Return the objectId.
	 * In case we already have an objectId, snapshot the existing mongo record, save it to our history, then replace it
	 * parentarray is the ids of the parent in this chain so we don't recurse
	 * @return [type] [description]
	 */
	public function save($reindex=true, $saveRevision=true, $noCache=false) {

		if(!isset($this->assetObject)) {
			return false;
		}
		$oldAsset = null;
		if($this->getObjectId() && $this->assetObject->getId()) {
			$oldAsset = new Asset_model;
        	$oldAsset->loadAssetById($this->getObjectId());
        	$oldAssetObject = clone $oldAsset->assetObject;
    	}

    	$this->assetObject->setWidgets($this->getWidgetArray());

		$this->assetObject->setModifiedAt(new DateTime());


		if($this->user_model && isset($this->user_model->user)) {
			$this->assetObject->setModifiedBy($this->user_model->getId());
			if(!$this->assetObject->getCreatedBy()) {
				$this->assetObject->setCreatedBy($this->user_model->getId());
			}
		}
		else {
			$this->assetObject->setCreatedBy(0);
			$this->assetObject->setModifiedBy(0);
		}

		$this->assetObject->setReadyForDisplay($this->getGlobalValue("readyForDisplay")?true:false);
		$this->assetObject->setCollectionId($this->getGlobalValue("collectionId"));
		$this->assetObject->setCollectionMigration($this->getGlobalValue("collectionMigration"));
		$this->assetObject->setTemplateId($this->templateId);
		if(is_object($this->getGlobalValue("availableAfter"))) {
			$this->assetObject->setAvailableAfter($this->getGlobalValue("availableAfter"));
		}
		else {
			$this->assetObject->setAvailableAfter(null);
		}

		$this->assetObject->setDeleted(false);

		if(!$this->getObjectId()) {
			$this->assetObject->setAssetId((string)new MongoDB\BSON\ObjectId());
			$this->doctrine->em->persist($this->assetObject);
			$this->doctrine->em->flush();
    	}
        else if($oldAsset) {

        	/**
        	 * if collection is changing, we need to check and see if we're changing buckets.
        	 * if so, we don't change the bucket right away, but rather set a flag for that and start a background process.
        	 * background process sets special forceCollection falg
        	 */

        	if($oldAsset->getGlobalValue("collectionId") != $this->getGlobalValue("collectionId") && !$this->forceCollection) {
        		$oldCollection = $this->collection_model->getCollection($oldAsset->getGlobalValue("collectionId"));
        		$newCollection = $this->collection_model->getCollection($this->getGlobalValue("collectionId"));


        		if($oldCollection && $newCollection) {
        			if($oldCollection->getBucket() != $newCollection->getBucket()) {
        				$this->assetObject->setCollectionId($oldAsset->getGlobalValue("collectionId"));
        				$this->assetObject->setCollectionMigration(true);

						$pheanstalk = new Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));
						$newTask = json_encode(["objectId"=>$this->getObjectId(),"instance"=>$this->instance->getId(), "targetCollection"=>$this->getGlobalValue("collectionId")]);
						$jobId= $pheanstalk->useTube('collectionMigration')->put($newTask, NULL, 1, 900);
        			}
        			else {
        				// collection is changing but bucket isn't, just update the handlers
        				$uploadHandlers = $this->getAllWithinAsset("Upload");
        				foreach($uploadHandlers as $uploadHandler) {
							foreach($uploadHandler->fieldContentsArray as $key=>$uploadContents) {
								$fileHandler = $uploadContents->getFileHandler();
								$fileHandler->setCollectionId($newCollection->getId());
								$fileHandler->save();
        					}
        				}
        			}
        		}
        	}

        	$this->doctrine->em->detach($oldAssetObject);
        	// $oldAssetObject->setId();
        	$oldAssetObject->setRevisionSource($this->assetObject);
        	$oldAssetObject->setAssetId(null);

        	$this->doctrine->em->persist($this->assetObject);

        	if($saveRevision) {
        		$this->doctrine->em->persist($oldAssetObject);
        	}

        	$this->doctrine->em->flush();

    	}
    	else {
    		$this->doctrine->em->persist($this->assetObject);
			$this->doctrine->em->flush();
    	}


		// if this asset isn't supposed to be available, don't add it to the index
		// TODO: also check template
		$noIndex=false;
		if($this->getGlobalValue('availableAfter')) {
			date_default_timezone_set('UTC');
			$afterDate = $this->getGlobalValue('availableAfter');
			if($afterDate > new DateTime()) {
				$noIndex=true;
			}
		}

		if($reindex && !$noIndex) {
			$pheanstalk = new Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));
			$newTask = json_encode(["objectId"=>$this->getObjectId(),"instance"=>$this->instance->getId()]);
			$jobId= $pheanstalk->useTube('reindex')->put($newTask, NULL, 1);
		}

		if($noIndex) {
			$this->load->model("search_model");
			// make sure the item isn't in the index
			$this->search_model->remove($this);
		}

		if(!$noCache) {
			$this->buildCache();
		}


    	return $this->getObjectId();
	}



	public function buildCache() {
		$this->useStaleCaches = FALSE;

		if($this->config->item('enableCaching')) {
			$this->doctrineCache->setNamespace('searchCache_');
			$this->doctrineCache->delete($this->getObjectId());
		}
		else {
			$this->flushCache();
		}

		if(!$this->assetObject) {
			return FALSE;
		}

		if(!$assetCache = $this->assetObject->getAssetCache()) {
			$assetCache = new Entity\AssetCache;
			$assetCache->setAsset($this->assetObject);
		}
		$assetCache->setNeedsRebuild(true);

		$assetCache->setTemplateId($this->templateId);

		// assets without valid collections are a problem.  Don't try to build a cache, just bail.
		if(!$this->collection_model->getCollection($this->getGlobalValue("collectionId"))) {
			$assetCache->setNeedsRebuild(false);
			$assetCache->setRebuildTimestamp(NULL);
			$this->doctrine->em->persist($assetCache);
			$this->doctrine->em->flush();
			return false;
		}

		$relatedItems = $this->getAllWithinAsset("Related_asset");
		$relatedAssetCache = array();
		foreach($relatedItems as $relatedWidget) {
			foreach($relatedWidget->fieldContentsArray as $widgetContents) {
				if($nestedAsset = $widgetContents->getRelatedAsset()) {
					$primaryHandlerId = NULL;
					try {
						$primaryHandler = $nestedAsset->getPrimaryFilehandler();
						$primaryHandlerId = $primaryHandler->getObjectId();

					}
					catch (Exception $e) {

					}
					$relatedAssetCache[$nestedAsset->getObjectId()] = ["relatedAssetTitle"=>$nestedAsset->getAssetTitle(), "primaryHandler"=>$primaryHandlerId, "readyForDisplay"=>$nestedAsset->getGlobalValue("readyForDisplay")?true:false];
				}
			}
		}

		$assetCache->setRelatedAssetCache($relatedAssetCache);

		try {
			$fileHandler = $this->getPrimaryFilehandler();
			$fileHandlerId = $fileHandler->getObjectId();
			$assetCache->setPrimaryHandlerCache($fileHandlerId);
		}
		catch (Exception $e) {
			$assetCache->setPrimaryHandlerCache(NULL);
		}

		$assetCache->setSearchResultCache($this->buildSearchResultEntry());

		$assetCache->setNeedsRebuild(false);
		$assetCache->setRebuildTimestamp(NULL);

		$this->doctrine->em->persist($assetCache);
		$this->doctrine->em->flush();

		return $assetCache;
	}

	public function flushCache() {

		$redisCache = new \Doctrine\Common\Cache\RedisCache();
        $redisCache->setRedis($this->doctrine->redisHost);
		$redisCache->setNamespace('searchCache_');
		$redisCache->delete($this->getObjectId());

	}

	// reindex self and children, preventing recursion

	public function reindex(&$parentArray=array()) {

		$this->load->model("search_model");

		if(count($parentArray)>5 ) {
			return;
		}

		$this->buildCache();

		$this->search_model->addOrUpdate($this);
		// now find any related items and resave them.
		//
		// we build a parent array so we don't recurse


		$results = $this->search_model->find(["searchText"=>$this->getObjectId(), "searchRelated"=>true], false);
		$parentArray[] = $this->getObjectId();

		foreach($results["searchResults"] as $result) {
			if(!in_array($result, $parentArray)) {
				echo "subIndex: " . $result . "\n";
				// $this->logging->logError("updating", $result);
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetById($result);
				$tempAsset->reindex($parentArray);
				if($this->config->item('enableCaching')) {
					$this->doctrineCache->setNamespace('searchCache_');
					$this->doctrineCache->delete($this->getObjectId());
				}

			}
		}

	}

	// TODO: should this really be within this class?
	public function getHandlerForId($fileId) {
		$uploadAssets = $this->getAllWithinAsset("Upload");

		foreach($uploadAssets as $uploadAsset) {
			foreach($uploadAsset->fieldContentsArray as $uploadAsset) {
				if($uploadAsset->getFileHandler() && $uploadAsset->getFileHandler()->getObjectId() == $fileId) {
					return $uploadAsset->getFileHandler();
				}
			}
		}
		return NULL;
	}

	public function getLastModifiedName() {
		$lastModifiedBy = "Unknown";
		if($this->getGlobalValue("modifiedBy") !== 0) {
			$lastModifiedId = $this->getGlobalValue("modifiedBy");
			$user = $this->doctrine->em->find('Entity\User', $lastModifiedId);
			$lastModifiedBy = $user->getDisplayName();
		}
		return $lastModifiedBy;
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
				if($fileHandler = $entry->getFileHandler()) {
					$fileHandler->deleteFile();
				}

			}
		}

		$oldAsset = new Asset_model;
        $oldAsset->loadAssetById($this->getObjectId());
        $oldAssetObject = $oldAsset->assetObject;

        $oldAssetObject->setDeleted(true);
        $oldAssetObject->setDeletedAt(new DateTime);
        $this->doctrine->em->persist($oldAssetObject);
        $this->doctrine->em->flush();
	}

}

/* End of file asset.php */
/* Location: ./application/models/asset.php */
