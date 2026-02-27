<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Log
 */
#[ORM\Table(name: 'logs')]
#[ORM\Entity]
class Log
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
    #[ORM\Column(name: 'task', type: 'string', nullable: true)]
    private $task;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'message', type: 'text', nullable: true)]
    private $message;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'url', type: 'text', nullable: true)]
    private $url;

     /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var \Entity\Instance
     */
    #[ORM\JoinColumn(name: 'instance_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \Entity\Instance::class)]
    private $instance;

    /**
     * @var \Entity\Collection
     */
    #[ORM\JoinColumn(name: 'collection_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\Collection::class)]
    private $collection;

    /**
     * @var \Entity\User
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\User::class)]
    private $user;



    /**
     * Set createdAt.
     *
     * @param \DateTime|null $createdAt
     *
     * @return Log
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
     * @return Log
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
     * Set task.
     *
     * @param string|null $task
     *
     * @return Log
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
     * @return Log
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
     * Set url.
     *
     * @param string|null $url
     *
     * @return Log
     */
    public function setUrl($url = null)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
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

    /**
     * Set instance.
     *
     * @param \Entity\Instance|null $instance
     *
     * @return Log
     */
    public function setInstance(?\Entity\Instance $instance = null)
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * Get instance.
     *
     * @return \Entity\Instance|null
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * Set collection.
     *
     * @param \Entity\Collection|null $collection
     *
     * @return Log
     */
    public function setCollection(?\Entity\Collection $collection = null)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Get collection.
     *
     * @return \Entity\Collection|null
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Set user.
     *
     * @param \Entity\User|null $user
     *
     * @return Log
     */
    public function setUser(?\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \Entity\User|null
     */
    public function getUser()
    {
        return $this->user;
    }
}
