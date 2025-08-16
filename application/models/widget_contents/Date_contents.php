<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Date_contents extends Widget_contents_base {

	public $start = ["text"=>null, "numeric"=>null];
	public $end = ["text"=>null, "numeric"=>null];
	public $range = false;
	public $label = null;

	public function __construct()
		{
			parent::__construct();
			//Do your magic here
		}

	/**
	 * Out of the gate, we're only supporting Geo points
	 * @return [type] [description]
	 */
	public function getAsArray($serializeNestedObjects=false) {
		return [
			"label" => $this->label,
			"start" => [
				"text" => $this->start["text"],
				"numeric" => $this->start["numeric"]
			],
			"end" => [
				"text" => $this->end["text"],
				"numeric" => $this->end["numeric"]
			],
			"range" => $this->range,
			"isPrimary" => $this->isPrimary
		];
	}

	public function getAsText($serializeNestedObjects=false) {

		$returnString = "";
		if($this->label) {
			$returnString = $this->label;
			if($this->start["text"]) {
				$returnString .= " : ";
			}
		}

		if($this->start["text"]) {
			$returnString .= $this->start["text"];
		}

		if($this->range) {
			$returnString .= " - " . $this->end["text"];
		}
		return $returnString;
	}

	public function loadContentFromArray($value) {
		foreach($value as $key=>$entry) {
			$this->$key = $entry;
			if($key == "isPrimary" && ($entry == true || $entry == "on")) {
				$this->isPrimary = true;
			}
		}
		if(count($this->end)>0 && strlen($this->end["text"]??"")>0) {
			$this->range = true;
		}
	}

	public function hasContents() {
		if($this->label != null || $this->start["text"] != null || $this->start['numeric'] != null) {
			return true;
		}
		else {
			return false;
		}
	}

	public function getContent() {
		return $this->start["text"];
	}

}

/* End of file widget_contents.php */
/* Location: ./application/models/widget_contents.php */