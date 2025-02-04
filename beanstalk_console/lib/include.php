<?php

/**
 * @link https://github.com/ptrofimov/beanstalk_console
 * @link http://kr.github.com/beanstalkd/
 * @author Petr Trofimov, Sergey Lysenko
 */
#spl_autoload_register(function($class) {
#    require_once str_replace('_', '/', $class) . '.php';
#});

require_once 'BeanstalkInterface.class.php';

$config['servers'] = ["127.0.0.1:11300"];
$server = !empty($_GET['server']) ? $_GET['server'] : '';
$server = $config['servers'][0];
$action = !empty($_GET['action']) ? $_GET['action'] : '';
$state = !empty($_GET['state']) ? $_GET['state'] : '';
$count = !empty($_GET['count']) ? $_GET['count'] : '';
$tube = !empty($_GET['tube']) ? $_GET['tube'] : '';
$tplMain = !empty($_GET['tplMain']) ? $_GET['tplMain'] : '';
$tplBlock = !empty($_GET['tplBlock']) ? $_GET['tplBlock'] : '';

class Console {

    public $interface;
    protected $_tplVars = array();
    protected $_globalVar = array();
    protected $_errors = array();
    private $servers = array();

    public function __construct($beanstalkIP) {
        $this->__init($beanstalkIP);
        $this->_main();
    }

    /** @return array */
    public function getServers() {
        return $this->servers;
    }

    public function getServerStats($server) {
        try {
            $interface = new BeanstalkInterface($server);
            $stats = $interface->getServerStats();
        } catch (Pheanstalk_Exception_ConnectionException $e) {
            $stats = array();
        }

        return $stats;
    }

    public function getServerStatsGroups() {
        return array(
                   'binlog' => array(
                'binlogCurrentIndex' => 'the index of the current binlog file being written to. If binlog is not active this value will be 0',
                'binlogMaxSize' => 'the maximum size in bytes a binlog file is allowed to get before a new binlog file is opened',
                'binlogOldestIndex' => 'the index of the oldest binlog file needed to store the current jobs',
                'binlogRecordsMigrated' => 'the cumulative number of records written as part of compaction',
                'binlogRecordsWritten' => 'the cumulative number of records written to the binlog',
            ),
            'cmd' => array(
                'cmdBury' => 'the cumulative number of bury commands',
                'cmdDelete' => 'the cumulative number of delete commands',
                'cmdIgnore' => 'the cumulative number of ignore commands',
                'cmdKick' => 'the cumulative number of kick commands',
                'cmdListTubeUsed' => 'the cumulative number of list-tube-used commands',
                'cmdListTubes' => 'the cumulative number of list-tubes commands',
                'cmdListTubesWatched' => 'the cumulative number of list-tubes-watched commands',
                'cmdPauseTube' => 'the cumulative number of pause-tube commands',
                'cmdPeek' => 'the cumulative number of peek commands',
                'cmdPeekBuried' => 'the cumulative number of peek-buried commands',
                'cmdPeekDelayed' => 'the cumulative number of peek-delayed commands',
                'cmdPeekReady' => 'the cumulative number of peek-ready commands',
                'cmdPut' => 'the cumulative number of put commands',
                'cmdRelease' => 'the cumulative number of release commands',
                'cmdReserve' => 'the cumulative number of reserve commands',
                'cmdStats' => 'the cumulative number of stats commands',
                'cmdStatsJob' => 'the cumulative number of stats-job commands',
                'cmdStatsTube' => 'the cumulative number of stats-tube commands',
                'cmdUse' => 'the cumulative number of use commands',
                'cmdWatch' => 'the cumulative number of watch commands',
            ),
            'current' => array(
                'currentConnections' => 'the number of currently open connections',
                'currentJobsBuried' => 'the number of buried jobs',
                'currentJobsDelayed' => 'the number of delayed jobs',
                'currentJobsReady' => 'the number of jobs in the ready queue',
                'currentJobsReserved' => 'the number of jobs reserved by all clients',
                'currentJobsUrgent' => 'the number of ready jobs with priority < 1024',
                'currentProducers' => 'the number of open connections that have each issued at least one put command',
                'currentTubes' => 'the number of currently-existing tubes',
                'currentWaiting' => 'the number of open connections that have issued a reserve command but not yet received a response',
                'currentWorkers' => 'the number of open connections that have each issued at least one reserve command',
            ),
            'other' => array(
                'hostname' => 'the hostname of the machine as determined by uname',
                'id' => 'a random id string for this server process, generated when each beanstalkd process starts',
                'jobTimeouts' => 'the cumulative count of times a job has timed out',
                'maxJobSize' => 'the maximum number of bytes in a job',
                'pid' => 'the process id of the server',
                'rusageStime' => 'the cumulative system CPU time of this process in seconds and microseconds',
                'rusageUtime' => 'the cumulative user CPU time of this process in seconds and microseconds',
                'totalConnections' => 'the cumulative count of connections',
                'totalJobs' => 'the cumulative count of jobs created',
                'uptime' => 'the number of seconds since this server process started running',
                'version' => 'the version string of the server',
            ),
        );
    }

    public function getTubeStatFields() {
        return array(
            'currentJobsUrgent' => 'number of ready jobs with priority < 1024 in this tube',
            'currentJobsReady' => 'number of jobs in the ready queue in this tube',
            'currentJobsReserved' => 'number of jobs reserved by all clients in this tube',
            'currentJobsDelayed' => 'number of delayed jobs in this tube',
            'currentJobsBuried' => 'number of buried jobs in this tube',
            'totalJobs' => 'cumulative count of jobs created in this tube in the current beanstalkd process',
            'currentUsing' => 'number of open connections that are currently using this tube',
            'currentWaiting' => 'number of open connections that have issued a reserve command while watching this tube but not yet received a response',
            'currentWatching' => 'number of open connections that are currently watching this tube',
            'pause' => 'number of seconds the tube has been paused for',
            'cmdDelete' => 'cumulative number of delete commands for this tube',
            'cmdPauseTube' => 'cumulative number of pause-tube commands for this tube',
            'pauseTimeLeft' => 'number of seconds until the tube is un-paused',
        );
    }

    public function getTubeStatGroups() {
        return array(
            'current' => array(
                'currentJobsBuried',
                'currentJobsDelayed',
                'currentJobsReady',
                'currentJobsReserved',
                'currentJobsUrgent',
                'currentUsing',
                'currentWaiting',
                'currentWatching',
            ),
            'other' => array(
                'cmdDelete',
                'cmdPauseTube',
                'pause',
                'pauseTimeLeft',
                'totalJobs',
            ),
        );
    }

    public function getTubeStatVisible() {
        if (!empty($_COOKIE['tubefilter'])) {
            return explode(',', $_COOKIE['tubefilter']);
        } else {
            return array(
                'currentJobsBuried',
                'currentJobsDelayed',
                'currentJobsReady',
                'currentJobsReserved',
                'currentJobsUrgent',
                'totalJobs',
            );
        }
    }

    public function getTubeStatValues($tube) {
        // make sure, that rapid tube disappearance (eg: anonymous tubes, don't kill the interface, as they might be missing)
        try {
            return $this->interface->_client->statsTube($tube);
        } catch (Pheanstalk_Exception_ServerException $ex) {
            if (strpos($ex->getMessage(), Pheanstalk_Response::RESPONSE_NOT_FOUND) !== false) {
                return array();
            } else {
                throw $ex;
            }
        }
    }

    protected function __init($beanstalkIP) {
        global $server, $action, $state, $count, $tube, $config, $tplMain, $tplBlock;
        $server=$beanstalkIP;
        $this->_globalVar = array(
            'server' => $server,
            'action' => $action,
            'state' => $state,
            'count' => $count,
            'tube' => $tube,
            '_tplMain' => $tplMain,
            '_tplBlock' => $tplBlock,
            'config' => $config);
        $this->_tplVars = $this->_globalVar;
        if (!in_array($this->_tplVars['_tplBlock'], array('allTubes'))) {
            unset($this->_tplVars['_tplBlock']);
        }
        if (!in_array($this->_tplVars['_tplMain'], array('main', 'ajax'))) {
            unset($this->_tplVars['_tplMain']);
        }
        if (empty($this->_tplVars['_tplMain'])) {
            $this->_tplVars['_tplMain'] = 'main';
        }

        $this->servers = $config['servers'];

        if (isset($_COOKIE['beansServers'])) {
            foreach (explode(';', $_COOKIE['beansServers']) as $server) {
                $this->servers[] = $server;
            }
        }
    }

    public function getErrors() {
        return $this->_errors;
    }

    public function getTplVars($var = null) {
        if (!empty($var)) {
            $result = !empty($this->_tplVars[$var]) ? $this->_tplVars[$var] : null;
        } else {
            $result = $this->_tplVars;
        }

        return $result;
    }

    protected function deleteAllFromTube($state, $tube) {
        try {
            do {
                switch ($state) {
                    case 'ready':
                        $job = $this->interface->_client->useTube($tube)->peekReady();
                        break;
                    case 'delayed':
                        $job = $this->interface->_client->useTube($tube)->peekDelayed();
                        break;
                    case 'buried':
                        $job = $this->interface->_client->useTube($tube)->peekBuried();
                        break;
                }

                if ($job) {
                    $this->interface->_client->delete($job);
                    set_time_limit(5);
                }
            } while (!empty($job));
        } catch (Exception $e) {
            // there might be no jobs to peek at, and peekReady raises exception in this situation
            echo $e->getMessage();
        }
    }

    protected function _main() {
        global $server;
        if (!isset($_GET['server']) && !isset($server)) {
            // execute methods without a server
            if (isset($_GET['action']) && in_array($_GET['action'], array('serversRemove'))) {
                $funcName = "_action" . ucfirst($this->_globalVar['action']);
                if (method_exists($this, $funcName)) {
                    $this->$funcName();
                }
                return;
            }
            return;
        }

        try {
            $this->interface = new BeanstalkInterface($this->_globalVar['server']);

            $this->_tplVars['tubes'] = $this->interface->getTubes();

            $stats = $this->interface->getTubesStats();

            $this->_tplVars['tubesStats'] = $stats;
            $this->_tplVars['peek'] = $this->interface->peekAll($this->_globalVar['tube']);
            $this->_tplVars['contentType'] = $this->interface->getContentType();
            if (!empty($_GET['action'])) {
                $funcName = "_action" . ucfirst($this->_globalVar['action']);
                if (method_exists($this, $funcName)) {
                    $this->$funcName();
                }
                return;
            }
        } catch (Pheanstalk_Exception_ConnectionException $e) {
            $this->_errors[] = 'The server is unavailable';
        } catch (Pheanstalk_Exception_ServerException $e) {
            // if we get response not found, we just skip it (as the peekAll reached a tube which no longer existed)
            if (strpos($e->getMessage(), Pheanstalk_Response::RESPONSE_NOT_FOUND) === false) {
                $this->_errors[] = $e->getMessage();
            }
        } catch (Exception $e) {
            $this->_errors[] = $e->getMessage();
        }
    }

    protected function _actionKick() {
        $this->interface->kick($this->_globalVar['tube'], $this->_globalVar['count']);
        header(
                sprintf('Location: index.php?server=%s&tube=%s', $this->_globalVar['server'], $this->_globalVar['tube']));
        exit();
    }

    protected function _actionDelete() {
        switch ($this->_globalVar['state']) {
            case 'ready':
                $this->interface->deleteReady($this->_globalVar['tube']);
                break;
            case 'delayed':
                $this->interface->deleteDelayed($this->_globalVar['tube']);
                break;
            case 'buried':
                $this->interface->deleteBuried($this->_globalVar['tube']);
                break;
        }

        $this->_postDelete();
    }

    protected function _postDelete() {
        $arr = $this->getTubeStatValues($this->_globalVar['tube']);
        $availableJobs = $arr['current-jobs-urgent'] + $arr['current-jobs-ready'] + $arr['current-jobs-reserved'] + $arr['current-jobs-delayed'] + $arr['current-jobs-buried'];
        if (empty($availableJobs)) {
            // make sure we redirect to all tubes, as this tube no longer exists
            $this->_globalVar['tube'] = null;
        }
        header(
                sprintf('Location: index.php?server=%s&tube=%s', $this->_globalVar['server'], $this->_globalVar['tube']));
        exit();
    }

    protected function _actionDeleteAll($tube = null) {
        if (empty($tube)) {
            $tube = $this->_globalVar['tube'];
        }
        $this->deleteAllFromTube($this->_globalVar['state'], $tube);
        $this->_postDelete();
    }

    protected function _actionServersRemove() {
        $server = $_GET['removeServer'];
        $this->servers = array_diff($this->servers, array($server));
        if (count($this->servers)) {
            setcookie('beansServers', implode(';', $this->servers), time() + 86400 * 365);
        } else {
            // no servers, clear cookie
            setcookie('beansServers', '', time() - 86400 * 365);
        }
        header('Location: index.php');
        exit();
    }

    protected function _actionAddjob() {
        $result = array('result' => false);

        $tubeName = !empty($_POST['tubeName']) ? $_POST['tubeName'] : '';
        $tubeData = !empty($_POST['tubeData']) ? stripcslashes($_POST['tubeData']) : '';
        $tubePriority = !empty($_POST['tubePriority']) ? $_POST['tubePriority'] : '';
        $tubeDelay = !empty($_POST['tubeDelay']) ? $_POST['tubeDelay'] : '';
        $tubeTtr = !empty($_POST['tubeTtr']) ? $_POST['tubeTtr'] : '';

        $id = $this->interface->addJob($tubeName, $tubeData, $tubePriority, $tubeDelay, $tubeTtr);

        if (!empty($result)) {
            $result = array('result' => true, 'id' => $result);
        }

        echo json_encode($result);
        exit();
    }

    protected function _actionReloader() {
        $this->_tplVars['_tplMain'] = 'ajax';
        $this->_tplVars['_tplBlock'] = 'allTubes';
    }

    protected function _actionClearTubes() {
        if (is_array($_POST)) {
            foreach ($_POST as $tube => $v) {
                $states = array('ready', 'delayed', 'buried');
                foreach ($states as $state) {
                    $this->deleteAllFromTube($state, $tube);
                }
            }
        }
        echo json_encode(array('result' => true));
        exit();
    }

}
