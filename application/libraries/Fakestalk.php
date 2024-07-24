<?php

// namespace Pheanstalk;

use Pheanstalk\PheanstalkInterface;

class Fakestalk {
    const VERSION = '3.0.2';

    private $_connection;
    private $_using = PheanstalkInterface::DEFAULT_TUBE;
    private $_watching = array(PheanstalkInterface::DEFAULT_TUBE => true);

    /**
     * @param string $host
     * @param int $port
     * @param int $connectTimeout
     * @param bool $connectPersistent
     */
    public function __construct($host=null, $port = PheanstalkInterface::DEFAULT_PORT, $connectTimeout = null, $connectPersistent = false)
    {
       
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
       
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    // ----------------------------------------

    /**
     * {@inheritdoc}
     */
    public function bury($job, $priority = PheanstalkInterface::DEFAULT_PRIORITY)
    {
       
    }

    /**
     * {@inheritdoc}
     */
    public function delete($job)
    {
      
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function ignore($tube)
    {
        

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function kick($max)
    {
       
        
    }

    /**
     * {@inheritdoc}
     */
    public function kickJob($job)
    {

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function listTubes()
    {
       
    }

    /**
     * {@inheritdoc}
     */
    public function listTubesWatched($askServer = false)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function listTubeUsed($askServer = false)
    {
        
    }

    /**
     * {@inheritdoc}
     */
    public function pauseTube($tube, $delay)
    {
   
    }

    /**
     * {@inheritdoc}
     */
    public function resumeTube($tube)
    {
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function peek($jobId)
    {
        
    }

    /**
     * {@inheritdoc}
     */
    public function peekReady($tube = null)
    {
       
    }

    /**
     * {@inheritdoc}
     */
    public function peekDelayed($tube = null)
    {
      
    }

    /**
     * {@inheritdoc}
     */
    public function peekBuried($tube = null)
    {
       
    }

    /**
     * {@inheritdoc}
     */
    public function put(
        $data,
        $priority = PheanstalkInterface::DEFAULT_PRIORITY,
        $delay = PheanstalkInterface::DEFAULT_DELAY,
        $ttr = PheanstalkInterface::DEFAULT_TTR
    )
    {
     
    }

    /**
     * {@inheritdoc}
     */
    public function putInTube(
        $tube,
        $data,
        $priority = PheanstalkInterface::DEFAULT_PRIORITY,
        $delay = PheanstalkInterface::DEFAULT_DELAY,
        $ttr = PheanstalkInterface::DEFAULT_TTR
    )
    {
      
    }

    /**
     * {@inheritdoc}
     */
    public function release(
        $job,
        $priority = PheanstalkInterface::DEFAULT_PRIORITY,
        $delay = PheanstalkInterface::DEFAULT_DELAY
    )
    {
      
    }

    /**
     * {@inheritdoc}
     */
    public function reserve($timeout = null)
    {
       
    }

    /**
     * {@inheritdoc}
     */
    public function reserveFromTube($tube, $timeout = null)
    {
      
    }

    /**
     * {@inheritdoc}
     */
    public function statsJob($job)
    {
        
    }

    /**
     * {@inheritdoc}
     */
    public function statsTube($tube)
    {
      
    }

    /**
     * {@inheritdoc}
     */
    public function stats()
    {
       
    }

    /**
     * {@inheritdoc}
     */
    public function touch($job)
    {
       
    }

    /**
     * {@inheritdoc}
     */
    public function useTube($tube)
    {
       
    }

    /**
     * {@inheritdoc}
     */
    public function watch($tube)
    {
     
    }

    /**
     * {@inheritdoc}
     */
    public function watchOnly($tube)
    {
        
    }

    // ----------------------------------------

    /**
     * Dispatches the specified command to the connection object.
     *
     * If a SocketException occurs, the connection is reset, and the command is
     * re-attempted once.
     *
     * @param  Command  $command
     * @return Response
     */
    private function _dispatch($command)
    {
       
    }

    /**
     * Creates a new connection object, based on the existing connection object,
     * and re-establishes the used tube and watchlist.
     */
    private function _reconnect()
    {
      
    }
}