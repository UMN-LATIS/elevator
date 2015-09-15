<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * JobLog
 */
class JobLog
{
    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var string
     */
    private $asset;

    /**
     * @var string
     */
    private $type;

    /**
     * @var integer
     */
    private $jobId;

    /**
     * @var string
     */
    private $task;

    /**
     * @var string
     */
    private $message;

    /**
     * @var integer
     */
    private $id;


    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return JobLog
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set asset
     *
     * @param string $asset
     * @return JobLog
     */
    public function setAsset($asset)
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * Get asset
     *
     * @return string 
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return JobLog
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set jobId
     *
     * @param integer $jobId
     * @return JobLog
     */
    public function setJobId($jobId)
    {
        $this->jobId = $jobId;

        return $this;
    }

    /**
     * Get jobId
     *
     * @return integer 
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * Set task
     *
     * @param string $task
     * @return JobLog
     */
    public function setTask($task)
    {
        $this->task = $task;

        return $this;
    }

    /**
     * Get task
     *
     * @return string 
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return JobLog
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
}
