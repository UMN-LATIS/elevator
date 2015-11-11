<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


defined('MOODLE_INTERNAL') || die('Invalid access');

global $CFG;
require_once $CFG->dirroot . '/repository/lib.php';
require_once(dirname(__FILE__).'/elevatorAPI.php');

/**
 * repository_elevator
 *
 * @package    repository_elevator
 */

class repository_elevator extends repository
{
    const ELEVATOR_MEDIA_PER_PAGE = 30;
    private $loggedIn = false;
    private $userId = null;
    private $repositoryId;
    private $elevatorAPI;

    private $fileTypes = null;
    private $manageURL = null;
    private $totalPages = 1;

    private $fileExtension = ".jpg"; // this will probably be .jpg unless we lie to handle SCORM, in which case this will be overwritten

    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {


        $options['page']    = optional_param('p', 1, PARAM_INT);
        parent::__construct($repositoryid, $context, $options);

        // if moodle is telling us what kind of mimetypes it wants, we check to see if it happens to be just ZIP.
        // If that's the case, we know we're on a SCORM page, because in all other cases supported_filetypes() will return web_image
        // this allows us to override our own type and act like a SCORM supplier.
        if (isset($options['mimetypes'])) {
            if (in_array(".zip", $options['mimetypes'])) {
                $this->fileTypes = "zipscorm";
                $this->fileExtension = ".zip";
            }
        }


        $this->setting = 'elevator_';
        $elevatorURL = get_config('elevator', 'elevatorURL');
        $apiKey = get_config('elevator', 'apiKey');
        $apiSecret = get_config('elevator', 'apiSecret');


        if (isset($options['userId'])) {
            $this->userId = $options['userId'];
        } else {
            $this->userId = get_user_preferences($this->setting.'_userId', '');
        }

        $this->repositoryId = $repositoryid;

        // initiate an instance of the elevator API, which handles all the backend communication.
        $this->elevatorAPI = new elevatorAPI($elevatorURL, $apiKey, $apiSecret, $this->userId);
        $this->elevatorAPI->fileTypes = $this->fileTypes;

        if ($this->userId) {
            $this->loggedIn = $this->testUserId();
            $this->manageURL = $this->elevatorAPI->getManageURL();
        }
    }

    // check to see if the user is cached on the remote side.  If not, we'll have to ask them to login.
    public function testUserId() {
        return $this->elevatorAPI->isCurrentUserLoggedIn($this->userId);
    }

    public function check_login() {
        return $this->loggedIn;
    }

    // Store the userid that we're passed back from elevator
    public function callback() {
        $userId  = optional_param('userId', '', PARAM_TEXT);
        set_user_preference($this->setting.'_userId', $userId);
    }


    public function logout() {
        set_user_preference($this->setting.'_userId', '');
        $this->userId    = '';
        return $this->print_login();
    }

    public function global_search() {
        return false;
    }

    public function getDrawers() {
        $drawerList = $this->elevatorAPI->getDrawers();
        $fileslist = array();
        foreach ($drawerList as $drawerId=>$drawerTitle) {
            $fileslist[] = array(
                'title' => $drawerTitle,
                'path'=> '/Home/Drawers/' . $drawerTitle,
                'children'=>array()
            );
        }
        return $fileslist;
    }

    public function getCollections() {
        $collectionList = $this->elevatorAPI->getCollections();
        foreach ($collectionList as $collectionId=>$collectionTitle) {
            $fileslist[] = array(
                'title' => $collectionTitle,
                'path'=> '/Home/Collections/' . $collectionTitle,
                'children'=>array()
            );
        }
        return $fileslist;
    }

    public function getAssetsFromDrawer($drawerTitle, $page) {
        $drawerList = $this->elevatorAPI->getDrawers();
        $drawerId = array_search($drawerTitle, $drawerList);
        $files = $this->elevatorAPI->getAssetsFromDrawer($drawerId, $page);
        return $this->parseResultIntoFileList($files, "/Home/Drawers/" . $drawerTitle);
    }

    public function getAssetsFromCollection($collectionTitle, $page) {
        $collectionList = $this->elevatorAPI->getCollections();
        $collectionId = array_search($collectionTitle, $collectionList);
        $files = $this->elevatorAPI->getAssetsFromCollection($collectionId, $page);
        return $this->parseResultIntoFileList($files, "/Home/Collections/" . $collectionTitle);

    }


    public function getAssetChildren($objectId, $page) {
        $childrenList = $this->elevatorAPI->getAssetChildren($objectId, $page);
        return $this->parseResultIntoFileList($childrenList, null);

    }

    public function parseResultIntoFileList($fileList, $path=null) {
        $outputArray = array();
        if (isset($fileList['totalResults']) && isset($fileList['assetsPerPage'])) {
            $this->totalPages = ceil($fileList["totalResults"] / $fileList["assetsPerPage"]);
        }

        foreach($fileList["matches"] as $entry) {
            if (array_key_exists("fileAssets", $entry) && $entry["fileAssets"] > 1) {
                // this allows us to browse into assets with multiple items
                $outputArray[] = array(
                    "shorttitle"=>$entry["title"],
                    "path"=>$path . "/" . $entry["objectId"],
                    "children" => array()
                    );
            }
            else {
                // is this an excerpt?
                if(isset($entry['excerpt']) && $entry['excerpt'] == true)  {
                    $outputArray[] = array(
                        "shorttitle"=>$entry['excerptLabel'],
                        "title"=>$entry['title'] . $this->fileExtension,
                        "source"=>"excerpt" . $entry['excerptId'],
                        "thumbnail"=>(isset($entry['primaryHandlerTiny'])?$entry['primaryHandlerTiny']:null)
                    );

                }
                else {
                    $outputArray[] = array(
                        "shorttitle"=>$entry['title'],
                        "title"=>$entry['title'] . $this->fileExtension,
                        "source"=>(isset($entry['primaryHandlerId'])?$entry['primaryHandlerId']:null),
                        "thumbnail"=>(isset($entry['primaryHandlerTiny'])?$entry['primaryHandlerTiny']:null)
                    );

                }
            }
        }
        return $outputArray;
    }


    public function get_listing($path = '', $page = '1') {
        if (!is_numeric($page)) {
            $page = 1;
        }
        global $OUTPUT;
        $filesList = array();

        if ($path == '' || $path == '/Home') {
            $filesList[] = array(
                'title' => "Drawers",
                'source' => "drawer",
                'path'=> '/Home/Drawers',
                'children'=>array()
            );
            $filesList[] = array(
                'title' => "Collections",
                'source' => "collections",
                'path'=> '/Home/Collections',
                'children'=>array()
            );
        }
        elseif ($path == "/Home/Drawers") {
            $filesList = $this->getDrawers();
        }
        elseif ($path == "/Home/Collections") {
            $filesList = $this->getCollections();
        }
        else {
            $splitPath = explode("/", $path);

            if (count($splitPath) == 2) {
                $filesList = $this->getAssetChildren($splitPath[1], $page);
            }
            else if (count($splitPath) == 4) {
                if($splitPath[2] == "Drawers") {
                    $filesList = $this->getAssetsFromDrawer($splitPath[3], $page);
                }

                if ($splitPath[2] == "Collections") {
                    $filesList = $this->getAssetsFromCollection($splitPath[3], $page);
                }
            }
            if (count($splitPath) == 5) {
                $filesList = $this->getAssetChildren($splitPath[4], $page);
            }

        }



        $list = array();
        $list['list'] = array();
        $list['manage'] = $this->manageURL;
        $list['dynload'] = true;
        $list['nosearch'] = false;
        $list['logouturl'] = '';
        $list['message'] = get_string('reset', 'repository_elevator');
        $list['list'] = $filesList;
        $list['pages'] = $this->totalPages;
        $list['page'] = $page;


        // process breadcrumb trail
        if (!empty($path)) {
            $trail = "";
            $parts = explode('/', $path);
            if (count($parts) > 1) {
                foreach ($parts as $part) {
                    if (!empty($part)) {
                        $trail .= ('/'.$part);
                        $list['path'][] = array('name'=>$part, 'path'=>$trail);
                    }
                }
            } else {
                $list['path'][] = array('name'=>$path, 'path'=>$path);
            }
        }
        else {
            $list['path'][] = array('name'=>"Home", 'path'=>'/Home');
        }

        return $list;
    }


    public function search($search_text, $page = 1) {
        global $SESSION;
        if (!is_numeric($page) || $page < 1) {
            $page = 1;
        }
        $sess_search_text = $this->setting.$this->id.'_search_text';
        if (!$search_text && isset($SESSION->{$sess_search_text})) {
            $search_text = $SESSION->{$sess_search_text};
        }

        $SESSION->{$sess_search_text} = $search_text;
        $searchResults= $this->elevatorAPI->search($search_text, $page);
        $search_result = array();
        $search_result['manage'] = $this->manageURL;
        $search_result['dynload'] = true;
        $search_result['nosearch'] = false;
        $search_result['logouturl'] = '';
        $search_result['message'] = get_string('reset', 'repository_elevator');
        $search_result['path'][] = array('name'=>"Home", 'path'=>'/Home');
        $search_result['list'] = $this->parseResultIntoFileList($searchResults);
        $search_result['pages'] = $this->totalPages;
        $search_result['page'] = $page;

        return $search_result;
    }

    // convert the thumbnail link into the full url for embedding
    public function get_link($url) {
        $assetInfo = $this->elevatorAPI->fileLookup($url);
        return $assetInfo["source"];
    }

    public function get_file_reference($source) {
        if ($this->fileTypes == "zipscorm") {

            $assetInfo = $this->elevatorAPI->fileLookup($source);
            return $assetInfo["original"];
        }
        else {
            // we shouldn't ever get here, but give them something.
            return $source;
        }
    }

    public function print_login($ajax = true) {
        global $CFG;
        $callbackurl = new moodle_url($CFG->wwwroot.'/repository/repository_callback.php', array(
            'callback'=>'yes',
            'repo_id'=>$this->repositoryId
            ));
        $url = $this->elevatorAPI->getLoginURL(http_build_query(array("callback"=>$callbackurl->out(false))));

        if ($this->options['ajax']) {
            $ret = array();
            $popup_btn = new stdClass();
            $popup_btn->type = 'popup';
            $popup_btn->url = $url;
            $ret['login'] = array($popup_btn);
            $ret['login_btn_label'] = "Login to Elevator";
            return $ret;
        } else {
            echo '<a target="_blank" href="'.$url.'">'.get_string('login', 'repository').'</a>';
        }
    }

    /**
     * What kind of files will be in this repository?
     *
     * @return array
     */
    public function supported_filetypes() {
        global $PAGE;
        if ($PAGE->pagetype == "mod-scorm-mod") {
            return "*";
        }
        else {
            return "web_image";
        }

    }

    /**
     * Tells how the file can be picked from this repository
     *
     * Returns FILE_EXTERNAL for all cases except scorm
     *
     * @return int
     */
    public function supported_returntypes() {
        global $PAGE;
        if ($PAGE->pagetype == "mod-scorm-mod") {
            return FILE_INTERNAL;
        }
        else {
            return FILE_EXTERNAL;
        }
    }

    /**
     * config stuff
     */

    public static function get_type_option_names() {
        return array('apiKey', 'apiSecret','elevatorURL', 'pluginname');
    }

     public static function type_config_form($mform, $classname = 'repository') {
        parent::type_config_form($mform);
        $mform->addElement('text', 'elevatorURL', get_string('elevatorURL', 'repository_elevator'));
        $mform->addElement('text', 'apiKey', get_string('apiKey', 'repository_elevator'));
        $mform->addElement('text', 'apiSecret', get_string('apiSecret', 'repository_elevator'));
        $mform->setType('elevatorURL', PARAM_TEXT);
        $mform->setType('apiKey', PARAM_TEXT);
        $mform->setType('apiSecret', PARAM_TEXT);


        $strrequired = get_string('required');
        $mform->addRule('elevatorURL', $strrequired, 'required', null, 'client');
        $mform->addRule('apiKey', $strrequired, 'required', null, 'client');
        $mform->addRule('apiSecret', $strrequired, 'required', null, 'client');
    }

}
