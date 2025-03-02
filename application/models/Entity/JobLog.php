<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * JobLog
 */
#[ORM\Table(name: 'job_logs')]
#[ORM\Index(name: 0, columns: ['asset'])]
#[ORM\Entity]
class JobLog
{
    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'createdAt', type: 'datetime', nullable: true)]
    private $createdAt;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'asset', type: 'string', nullable: true)]
    private $asset;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'type', type: 'string', nullable: true)]
    private $type;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'jobId', type: 'integer', nullable: true)]
    private $jobId;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'task', type: 'string', nullable: true)]
    private $task;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'message', type: 'text', nullable: true)]
    private $message;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'job_logs_id_seq', allocationSize: 1, initialValue: 1)]
    private $id;



    /**
     * Set createdAt.
     *
     * @param \DateTime|null $createdAt
     *
     * @return JobLog
     */
    public function setCreatedAt($createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set asset.
     *
     * @param string|null $asset
     *
     * @return JobLog
     */
    public function setAsset($asset = null)
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * Get asset.
     *
     * @return string|null
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * Set type.
     *
     * @param string|null $type
     *
     * @return JobLog
     */
    public function setType($type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set jobId.
     *
     * @param int|null $jobId
     *
     * @return JobLog
     */
    public function setJobId($jobId = null)
    {
        $this->jobId = $jobId;

        return $this;
    }

    /**
     * Get jobId.
     *
     * @return int|null
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * Set task.
     *
     * @param string|null $task
     *
     * @return JobLog
     */
    public function setTask($task = null)
    {
        $this->task = $task;

        return $this;
    }

    /**
     * Get task.
     *
     * @return string|null
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * Set message.
     *
     * @param string|null $message
     *
     * @return JobLog
     */
    public function setMessage($message = null)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return string|null
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
