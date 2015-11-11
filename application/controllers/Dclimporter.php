<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * dcl_works X
 * dcl_agent X
 * wk_title X
 * wk_event X
 * wk_measure X
 * src_pub
 * orders
 * ag_work
 * dcl_Views
 * mb_files
 *
 */

class dclImporter extends Instance_Controller {

	public $wkid = null;
	public $vwid = null;
	public $agid = null;
	public $srcid =null;
	public $ordid =null;
	public $digitalid = null;
	public $primaryViewId = null;
	public $targetCollection = 35;

	public $rootPathToMedia = "/export/A24FLUN0P1/archive_root/dcl/";

	public function __construct()
	{
		parent::__construct();
		ini_set("memory_limit","4096M");
		$this->dcl = $this->load->database('old', TRUE);
		$this->instance = $this->doctrine->em->find("Entity\Instance", 1);
		$this->load->model("asset_model");
		$this->user_model->loadUser(1);
		echo "\n";

	}


	public function importFromFile($file) {
		set_time_limit(0);
		ini_set('max_execution_time', 0);
		$this->db->query("SET SESSION wait_timeout = 28800 ");
		$contents = file_get_contents($file);
		$lines = explode("\n", $contents);

		$count = 0;
		foreach($lines as $entry) {
			$this->wkid = null;
			$this->vwid = null;
			$this->agid = null;
			$this->srcid =null;
			$this->ordid =null;
			$this->digitalid = null;
			echo "Importing " . $entry . "\n";
			$this->importId($entry);
			$count++;
			if($count % 100 == 0) {
				gc_collect_cycles();
			}
		}

	}




	public function regenerate($parentId) {

		$this->asset_model->loadAssetById($parentId);
		$related = $this->asset_model->getAllWithinAsset("Related_asset", null,0);
		foreach($related as $relate) {
			foreach($relate->fieldContentsArray as $newAsset) {
				$uploads = $newAsset->getRelatedAsset()->getAllWithinAsset("Upload");
				foreach($uploads as $upload) {
					foreach($upload->fieldContentsArray as $uploadContents) {

						$uploadContents->fileHandler->regenerate = true;
						$uploadContents->fileHandler->save();

					}
				}
			}
		}
		$uploads = $this->asset_model->getAllWithinAsset("Upload", null,0);
		foreach($uploads as $upload) {
			foreach($upload->fieldContentsArray as $uploadContents) {
				$uploadContents->fileHandler->regenerate = true;
				$uploadContents->fileHandler->save();
			}
		}


	}

	public function findCollection($collectionId) {

		$this->dcl->where("col_id", $collectionId);
		$collection = $this->dcl->get("collections");
		if($collection->num_rows() > 0) {
			$collectionResult = $collection->row();


			$result = $this->doctrine->em->getRepository("Entity\Collection")->findOneBy(["title"=>$collectionResult->name]);
			if($result) {
				return $result->getId();
			}



		}
		return 35;

	}


	public function importId($digitalId) {

		$this->dcl->where("digital_id", $digitalId);
		$viewRow = $this->dcl->get("dcl_views")->row();

		if(!$viewRow) {
			$this->logging->logError("import failed", "importing " . $digitalId . " failed");
			return;
		}

		$this->vwid = $viewRow->vw_id;
		$this->wkid = $viewRow->wk_id;
		$this->digitalid = $digitalId;
		$this->agid = $viewRow->view_agent_id;
		$this->ordid = $viewRow->ord_id;

		$this->dcl->where("wk_id", $this->wkid);
		$wkresult = $this->dcl->get("dcl_works");
		if($wkresult->num_rows()>0) {
			$work = $wkresult->row();
			$this->targetCollection = $this->findCollection($work->col_id);
		}

		if($this->wkid) {
			$this->importWork();
			$this->importWorkTitle();
			$this->importWorkEvent();
			$this->importWorkMeasure();
		}

		$this->importAgent();

		if($this->wkid) {
			$this->dcl->where("wk_id", $this->wkid);
			$result = $this->dcl->get("agents_works");
			foreach($result->result() as $entry) {
				$this->agid = $entry->ag_id;
				$this->importAgent();
			}
			$this->importAgentWork();
		}


		if($this->ordid) {
			$this->dcl->where("ord_id", $this->ordid);
			$orderRow = $this->dcl->get("orders")->row();
			$this->srcid = $orderRow->src_id;
			$this->importSourcePublication();
			$this->importOrder();
		}
		if($this->vwid) {
			$this->importViews();
			$this->importMediaBank();
		}

		echo "done!\n";

	}

	public function importWork() {
		$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 14)
			    ->where('work_id.fieldContents', $this->wkid)
			    ->getOne('dcl3');
		if($foundMongo) {
			return;
		}
		$this->dcl->where("wk_id", $this->wkid);
		$result = $this->dcl->get("dcl_works", 1);
		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);


			$newEntry["work_id"][]["fieldContents"] = $entry["wk_id"];
			$newEntry["style_period"][]["fieldContents"] = $entry["style_period1"];
			$newEntry["style_period"][]["fieldContents"] = $entry["style_period2"];
			$newEntry["style_period"][]["fieldContents"] = $entry["style_period3"];
			$newEntry["style_period"][]["fieldContents"] = $entry["style_period4"];
			if($entry["type4"]) {
				$newEntry["type"][]["fieldContents"] = $entry["type4"];
			}
			if($entry["type3"]) {
				$newEntry["type"][]["fieldContents"] = $entry["type3"];
			}
			if($entry["type2"]) {
				$newEntry["type"][]["fieldContents"] = $entry["type2"];
			}
			if($entry["type1"]) {
				$newEntry["type"][]["fieldContents"] = $entry["type1"];
			}

			$newEntry["culture"][]["fieldContents"] = $entry["culture1"];
			$newEntry["culture"][]["fieldContents"] = $entry["culture2"];
			$newEntry["technique"][]["fieldContents"] = $entry["technique1"];
			$newEntry["materials"][]["fieldContents"] = $entry["materials"];
			$newEntry["language"][]["fieldContents"] = $entry["language"];
			$newEntry["state_edition"][]["fieldContents"] = $entry["state_edition"];
			$newEntry["inscription"][]["fieldContents"] = $entry["inscription"];
			$newEntry["repository_object_id"][]["fieldContents"] = $entry["repository_object_id"];
			$newEntry["comments"][]["fieldContents"] = $entry["comments"];
			if($entry['primary_view_digital_id']) {
				$this->primaryViewId = $entry['primary_view_digital_id'];
			}

			$newEntry["collectionId"] = $this->targetCollection;
			$newEntry["templateId"] = 14;
			$newEntry["readyForDisplay"] = true;

			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->loadDataFromObject($newEntry);
			$objectId = $asset->save();
			echo "Work:" . $objectId. "\n";


		}
	}

	function isArrayEmpty($sourceArray, $targetKeys) {
		foreach($sourceArray as $key=>$value) {

			if(in_array($key, $targetKeys)) {
				if(!empty($value)) {
					return false;
				}
			}
		}
		return true;
	}


	public function importWorkTitle() {
		$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 16)
			    ->where('work_id.fieldContents', $this->wkid)
			    ->getOne('dcl3');
		if($foundMongo) {
			return;
		}
		$this->dcl->where("wk_id", $this->wkid);
		$result = $this->dcl->get("work_titles");
		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);

			if($this->isArrayEmpty($entry, ["title", "type"])) {
				echo "Work title Empty\n";
				return;
			}

			$newEntry["work_title_id"][]["fieldContents"] = $entry["wkt_id"];
			$newEntry["work_id"][]["fieldContents"] = $entry["wk_id"];
			$newEntry["title"][]["fieldContents"] = $entry["title"];
			$newEntry["type"][]["fieldContents"] = $entry["type"];
			$newEntry["mark_preferred"][]["fieldContents"] = $entry["mark_preferred"];
			$newEntry["collectionId"] = $this->targetCollection;
			$newEntry["templateId"] = 16;
			$newEntry["readyForDisplay"] = true;

			if(!$entry["title"] && !$entry["type"]) {
				echo "continuig\n";
				continue;
			}

			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->loadDataFromObject($newEntry);
			$objectId = $asset->save();
			echo "Work Title:" . $objectId. "\n";

			$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 14)
			    ->where('work_id.fieldContents', $entry['wk_id'])
			    ->getOne('dcl3');
			if($foundMongo) {
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetFromRecord($foundMongo);
				$assetArray = $tempAsset->getAsArray();
				$insert = array();
				$insert["targetAssetId"] = $objectId;
				if($entry["mark_preferred"] == "Y") {
					$insert["isPrimary"] = true;
				}
				$assetArray["work_title"][] = $insert;
				$tempAsset->loadDataFromObject($assetArray);
				$tempAsset->save();

			}


		}
	}

	public function importWorkEvent() {
				$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 17)
			    ->where('work_id.fieldContents', $this->wkid)
			    ->getOne('dcl3');
		if($foundMongo) {
			return;
		}
		$this->dcl->where("wk_id", $this->wkid);
		$result = $this->dcl->get("work_events");
		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);

			if($this->isArrayEmpty($entry, ["earliest_date", "latest_date", "begin_century", "end_century", "decade", "location_name", "continent", "country", "state", "region", "type", "city_site"])) {
				echo "Work Event Empty\n";
 				return;
 			}

			$newEntry["work_event_id"][]["fieldContents"] = $entry["wke_id"];
			$newEntry["work_id"][]["fieldContents"] = $entry["wk_id"];
			$newEntry["earliest_date"][]["fieldContents"] = $entry["earliest_date"];
			$newEntry["latest_date"][]["fieldContents"] = $entry["latest_date"];
			$newEntry["begin_century"][]["fieldContents"] = $entry["begin_century"];
			$newEntry["end_century"][]["fieldContents"] = $entry["end_century"];
			$newEntry["decade"][]["fieldContents"] = $entry["decade"];
			$newEntry["location_name"][]["fieldContents"] = $entry["location_name"];
			$newEntry["continent"][]["fieldContents"] = $entry["continent"];
			$newEntry["country"][]["fieldContents"] = $entry["country"];
			$newEntry["state"][]["fieldContents"] = $entry["state"];
			$newEntry["region"][]["fieldContents"] = $entry["region"];
			$newEntry["type"][]["fieldContents"] = $entry["type"];
			$newEntry["city_site"][]["fieldContents"] = $entry["city_site"];
			$newEntry["address"][]["fieldContents"] = $entry["address"];
			$newEntry["county"][]["fieldContents"] = $entry["county"];
			if($entry['longitude'] && $entry['latitude']) {

				$locArray = ["type"=>"Point", "coordinates"=>[$entry['longitude'], $entry['latitude']]];
				$locationEntry['loc'] = $locArray;
				$locationEntry['locationLabel'] = "";
				$newEntry['location'][] = $locationEntry;

			}
			$newEntry["readyForDisplay"] = true;

			$newEntry["collectionId"] = $this->targetCollection;
			$newEntry["templateId"] = 17;

			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->loadDataFromObject($newEntry);
			$objectId = $asset->save();
			echo "Work Event:" . $objectId. "\n";

			$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 14)
			    ->where('work_id.fieldContents', $entry['wk_id'])
			    ->getOne('dcl3');
			if($foundMongo) {
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetFromRecord($foundMongo);
				$assetArray = $tempAsset->getAsArray();
				$assetArray["work_event"][]["targetAssetId"] = $objectId;
				$tempAsset->loadDataFromObject($assetArray);
				$tempAsset->save();

			}
		}
	}

	public function importWorkMeasure() {
				$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 18)
			    ->where('work_id.fieldContents', $this->wkid)
			    ->getOne('dcl3');
		if($foundMongo) {
			return;
		}
		$this->dcl->where("wk_id", $this->wkid);
		$result = $this->dcl->get("work_measures");
		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);

			if($this->isArrayEmpty($entry, ["measurement", "extent"])) {
				echo "Work measure Empty\n";
				return;
 			}


			$newEntry["work_measurement_id"][]["fieldContents"] = $entry["wkm_id"];
			$newEntry["work_id"][]["fieldContents"] = $entry["wk_id"];
			$newEntry["measurement"][]["fieldContents"] = $entry["measurement"];
			$newEntry["extent"][]["fieldContents"] = $entry["extent"];
			$newEntry["readyForDisplay"] = true;
			$newEntry["collectionId"] = $this->targetCollection;
			$newEntry["templateId"] = 18;

			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->loadDataFromObject($newEntry);
			$objectId = $asset->save();
			echo "Work Measure" . $objectId. "\n";

			$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 14)
			    ->where('work_id.fieldContents', $entry['wk_id'])
			    ->getOne('dcl3');
			if($foundMongo) {
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetFromRecord($foundMongo);
				$assetArray = $tempAsset->getAsArray();
				$assetArray["work_measurement"][]["targetAssetId"] = $objectId;
				$tempAsset->loadDataFromObject($assetArray);
				$tempAsset->save();

			}
		}
	}

	// get all the values for a key from a multidimensional array
	function array_value_recursive($key, array $arr){
	    $val = array();
	    array_walk_recursive($arr, function($v, $k) use($key, &$val){
	        if($k == $key) array_push($val, $v);
	    });
	    return count($val) > 1 ? $val : array_pop($val);
	}


	public function importAgent() {

		$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 15)
			    ->where('agent_id.fieldContents',  $this->agid)
			    ->getOne('dcl3');
			if($foundMongo) {
				return;
			}
		$this->dcl->where("ag_id", $this->agid);
		$result = $this->dcl->get("dcl_agents");
		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);

			$newEntry["first_name_qualifier"][]["fieldContents"] = $entry["first_name_qualifier"];
			$newEntry["first_name"][]["fieldContents"] = $entry["first_name"];
			$newEntry["last_name"][]["fieldContents"] = $entry["last_name"];
			$newEntry["last_name_qualifier"][]["fieldContents"] = $entry["last_name_qualifier"];
			$newEntry["alt_name"][]["fieldContents"] = $entry["alt_name"];
			$newEntry["birth_date"][]["fieldContents"] = $entry["birth_date"];
			$newEntry["death_date"][]["fieldContents"] = $entry["death_date"];
			$newEntry["nationality"][]["fieldContents"] = $entry["nationality1"];
			$newEntry["nationality"][]["fieldContents"] = $entry["nationality2"];
			$newEntry["dates_active"][]["fieldContents"] = $entry["dates_active"];
			$newEntry["notes"][]["fieldContents"] = $entry["notes"];

			// if we don't have any values up to this point, let's bail.
			if(!array_filter($this->array_value_recursive("fieldContents", $newEntry))) {
				return;
			}

			$newEntry["agent_id"][]["fieldContents"] = $entry["ag_id"];


			$newEntry["templateId"] = 15;
			$newEntry["collectionId"] = $this->targetCollection;
			$newEntry["readyForDisplay"] = true;

			$agentNameArray = array($entry["first_name_qualifier"], join($this->removeEmptyElements(array($entry["first_name"], $entry["last_name"])), " "),$entry["last_name_qualifier"]);
			$agentName = join($this->removeEmptyElements($agentNameArray), ", ");
			$newEntry["display_name"][]["fieldContents"] = $agentName;


			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->loadDataFromObject($newEntry);
			$objectId = $asset->save();
			echo "Agent:" . $objectId. "\n";
		}

	}

	public function removeEmptyElements($myArray) {

			foreach ($myArray as $key => $value) {
		      if (is_null($value) || $value=="") {
		        unset($myArray[$key]);
		      }
		   }
		return $myArray;

	}

	public function importSourcePublication() {

		$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 19)
			    ->where('source_id.fieldContents', $this->srcid)
			    ->getOne('dcl3');
		if($foundMongo) {
			return;
		}

		$this->dcl->where("src_id", $this->srcid);
		$result = $this->dcl->get("source_publications");
		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);

			$newEntry["source_id"][]["fieldContents"] = $entry["src_id"];
			$newEntry["author"][]["fieldContents"] = $entry["author"];
			$newEntry["article_title"][]["fieldContents"] = $entry["article_title"];
			$newEntry["title"][]["fieldContents"] = $entry["title"];
			$newEntry["volume"][]["fieldContents"] = $entry["volume"];
			$newEntry["number"][]["fieldContents"] = $entry["number"];
			$newEntry["year"][]["fieldContents"] = $entry["year"];
			$newEntry["readyForDisplay"] = true;
			$newEntry["templateId"] = 19;
			$newEntry["collectionId"] = $this->targetCollection;

			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->loadDataFromObject($newEntry);
			$objectId = $asset->save();
			echo "Source Pub:" . $objectId. "\n";
		}

	}

public function importOrder() {

	$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 20)
			    ->where('order_id.fieldContents', $this->ordid)
			    ->getOne('dcl3');
		if($foundMongo) {
			return;
		}

	$this->dcl->where("ord_id", $this->ordid);
		$result = $this->dcl->get("orders");
		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);

			$newEntry["order_id"][]["fieldContents"] = $entry["ord_id"];
			$newEntry["collection_id"][]["fieldContents"] = $entry["col_id"];
			$newEntry["source_id"][]["fieldContents"] = $entry["src_id"];
			$newEntry["ordered_by"][]["fieldContents"] = $entry["ordered_by"];
			$newEntry["readyForDisplay"] = true;
			$newEntry["templateId"] = 20;
			$newEntry["collectionId"] = $this->targetCollection;

			if($entry['src_id']) {
				$foundMongo = $this->qb
				    ->where('collectionId', $this->targetCollection)
				    ->where('templateId', 19)
				    ->where('source_id.fieldContents', $entry['src_id'])
				    ->getOne('dcl3');
				if($foundMongo) {
					$tempAsset = new Asset_model();
					$tempAsset->loadAssetFromRecord($foundMongo);

					$newEntry["source"][]["targetAssetId"] = $tempAsset->getObjectId();

				}
			}


			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->loadDataFromObject($newEntry);
			$objectId = $asset->save();
			echo "Order:" . $objectId. "\n";
		}

	}


public function importAgentWork() {

	$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 21)
			    ->where('work_id.fieldContents', $this->wkid)
			    ->getOne('dcl3');
		if($foundMongo) {
			return;
		}
	$this->dcl->where("wk_id", $this->wkid);
		$result = $this->dcl->get("agents_works");
		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);

			$newEntry["agent_work_id"][]["fieldContents"] = $entry["agwk_id"];
			$newEntry["agent_id"][]["fieldContents"] = $entry["ag_id"];
			$newEntry["work_id"][]["fieldContents"] = $entry["wk_id"];
			$newEntry["role"][]["fieldContents"] = $entry["role"];
			$newEntry["attribution"][]["fieldContents"] = $entry["attribution"];
			$newEntry["extent"][]["fieldContents"] = $entry["extent"];
			$newEntry["readyForDisplay"] = true;
			$newEntry["templateId"] = 21;
			$newEntry["collectionId"] = $this->targetCollection;
			$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 15)
			    ->where('agent_id.fieldContents', $entry['ag_id'])
			    ->getOne('dcl3');
			if($foundMongo) {
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetFromRecord($foundMongo);
				$newEntry["agent"][]["targetAssetId"] = $tempAsset->getObjectId();
			}
			else {
				return;
			}



			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->loadDataFromObject($newEntry);
			$objectId = $asset->save();
			echo "Agentwork:" . $objectId. "\n";

			$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 14)
			    ->where('work_id.fieldContents', $entry['wk_id'])
			    ->getOne('dcl3');
			if($foundMongo) {
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetFromRecord($foundMongo);
				$assetArray = $tempAsset->getAsArray();
				$insert = array();
				$insert["targetAssetId"] = $objectId;
				if($entry["rank"] == 1) {
					$insert["isPrimary"] = true;
				}
				$assetArray["agent"][] = $insert;
				$tempAsset->loadDataFromObject($assetArray);
				$tempAsset->save();

			}
		}

	}

	public function importViews()
	{

	$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 13)
			    ->where('view_id.fieldContents', $this->vwid)
			    ->getOne('dcl3');
		if($foundMongo) {
			return;
		}

		$this->dcl->where("vw_id", $this->vwid);
		$result = $this->dcl->get("dcl_views");

		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);

		    $newEntry["alt_type"][]["fieldContents"] = $entry["alt_type"];
		    $newEntry["classification"][]["fieldContents"] = $entry["classification"];
		    $newEntry["date"][]["fieldContents"] = $entry["date"];
		    $newEntry["description"][]["fieldContents"] = $entry["description"];
		    $newEntry["digital_id"][]["fieldContents"] = $entry["digital_id"];
		    $newEntry["digitized"][]["fieldContents"] = $entry["digitized"];
		    $newEntry["figure_number"][]["fieldContents"] = $entry["figure_number"];
		    $newEntry["folio_number"][]["fieldContents"]= $entry["folio_number"];
		    $newEntry["keywords"][]["tags"] = $entry["keywords"];

		    $newEntry["media_type"][]["fieldContents"] = $entry["media_type"];
		    $newEntry["order_id"][]["fieldContents"] = $entry["ord_id"];
		    $newEntry["legacy_id"][]["fieldContents"] = $entry["legacy_id"];
		    $newEntry["page_number"][]["fieldContents"] = $entry["page_number"];
		    $newEntry["public_copyright"][]["fieldContents"] = $entry["copyright_public"];
		    $newEntry["scale"][]["fieldContents"] = $entry["scale"];
		    $newEntry["sub_type"][]["fieldContents"] = $entry["sub_type"];
		    $newEntry["title"][]["fieldContents"] = $entry["title"];
		    $newEntry["type"][]["fieldContents"] = $entry["type"];
		    $newEntry["view_agent_extent"][]["fieldContents"] = $entry["view_agent_extent"];
		    $newEntry["view_agent_id"][]["fieldContents"] = $entry["view_agent_id"];
		    $newEntry["view_id"][]["fieldContents"] = $entry["vw_id"];
		    $newEntry["work_id"][]["fieldContents"] = $entry["wk_id"];
		    $newEntry["copyright_full_video"][]["fieldContents"] = $entry["copyright_full_video"];
			$newEntry["readyForDisplay"] = true;
		    $newEntry["collectionId"] = $this->targetCollection;
			$newEntry["templateId"] = 13;

			$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 15)
			    ->where('agent_id.fieldContents', $entry['view_agent_id'])
			    ->getOne('dcl3');
			if($foundMongo) {
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetFromRecord($foundMongo);
				$newEntry["agent"][]["targetAssetId"] = $tempAsset->getObjectId();
			}

			$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 20)
			    ->where('order_id.fieldContents', $entry['ord_id'])
			    ->getOne('dcl3');
			if($foundMongo) {
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetFromRecord($foundMongo);
				$newEntry["order"][]["targetAssetId"] = $tempAsset->getObjectId();
			}



			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->loadDataFromObject($newEntry);
			$objectId = $asset->save();
			echo "View:" . $objectId. "\n";

			$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 14)
			    ->where('work_id.fieldContents', $entry['wk_id'])
			    ->getOne('dcl3');
			if($foundMongo) {
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetFromRecord($foundMongo);
				$assetArray = $tempAsset->getAsArray();
				$insert = array();
				$insert["targetAssetId"] = $objectId;
				if(strcasecmp($this->primaryViewId, $entry["digital_id"]) == 0) {
					$insert["isPrimary"] = true;
				}
				$assetArray["view"][] = $insert;

				$tempAsset->loadDataFromObject($assetArray);
				$tempAsset->save();

			}

		}
	}



	public function importMediaBank() {
		$this->dcl->where("digital_id", $this->digitalid);
		$this->dcl->where("is_active_for_delivery", 1);
		$result = $this->dcl->get("source_medias");
		foreach($result->result_array() as $entry) {


			$mediaId = $entry["id"];
			$digitalId = $entry["digital_id"];
			$originalExtension = str_replace(".", "", $entry["file_extension"]);



		$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 13)
			    ->where('view_id.fieldContents', $this->vwid)
			    ->getOne('dcl3');
		if($foundMongo && isset($foundMongo["file_asset"]) && count($foundMongo["file_asset"])>0) {
			echo "This view, " . $this->vwid . " " . $foundMongo["_id"] . "  already has files\n";
			return;
		}


			$foundMongo = $this->qb
			    ->where('collectionId', $this->targetCollection)
			    ->where('templateId', 13)
			    ->where('digital_id.fieldContents', $digitalId)
			    ->getOne('dcl3');

			if($foundMongo) {
				echo "starting\n";
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetFromRecord($foundMongo);


				// try {
				// 	$tempAsset->getPrimaryFilehandler();
				// }
				// catch(Exception $e) {
				// 	// have a file handler already;
				// 	return;
				// }

				$filename = $mediaId . ".orig";
 				$pathToFile = $this->rootPathToMedia . "/" . $this->pathToMedia($mediaId) . "/" . $filename;

 				if(!file_exists($pathToFile)) {
 					echo "File Not Found: " . $pathToFile . "\n";
 					return;
 				}

 				$fileHandler = $this->filehandler_router->getHandlerForType($originalExtension);

				if(get_class($fileHandler) == "FileHandlerBase") {
 					echo "unkown type: " . $originalExtension . "\n";
 					die;
 				}
 				$fileHandler->setCollectionId($tempAsset->getGlobalValue("collectionId"));
 				$fileHandler->parentObjectId = $tempAsset->getObjectId();

				$fileContainer = new fileContainerS3();
				$fileHandler->sourceFile = $fileContainer;

				$fileContainer->path = "original";
				$fileContainer->storageType = $this->instance->getS3StorageType();
				$fileContainer->derivativeType = "source";
				$fileContainer->setParent($fileHandler);
				$fileContainer->originalFilename = $digitalId . ".". $originalExtension;
				$fileHandler->save();

				$objectId = $fileHandler->getObjectId();

				if(!$fileHandler->s3model->putObject($pathToFile, "original" . "/" . $fileHandler->getReversedObjectId() . "-source")) {
					echo "issue with " . $objectId . " " . $digitalId . "\n";
					die;
				}
				$fileHandler->sourceFile->ready = true;
				$fileHandler->save();

				$assetArray = $tempAsset->getAsArray();
				$assetArray["file_asset"][] = ["fileId"=>$objectId, "regenerate"=>"On"];

				$tempAsset->loadDataFromObject($assetArray);
				echo $tempAsset->getObjectId() . "\n";
				echo $objectId . "\n";
				$tempAsset->save();
 			}
 			else {
 				$this->logging->logError("no match", "could not find match for " . $digitalId);
 				echo "could not find match for " . $digitalId . "\n";
 			}





		}
	}


	function pathToMedia($mediaId) {
		$reversedMediaId = strrev($mediaId);
		$stringLength = strlen($reversedMediaId);


		$newPathArray = array();
		for($i=0; $i<$stringLength/2; $i++) {
			if(1+$i*2 < $stringLength) {
				$newPathArray[] = str_pad(substr($reversedMediaId, 2*$i, 2), 2, 0, STR_PAD_LEFT);
			}
		}

		$newPath = implode("/", $newPathArray);
		return $newPath;

	}

}

/* End of file  */
/* Location: ./application/controllers/ */
