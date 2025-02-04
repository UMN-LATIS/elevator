<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RecentCollection
 */
class RecentCollection
{
    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Entity\User
     */
    private $user;

    /**
     * @var \Entity\Collection
     */
    private $collection;

    /**
     * @var \Entity\Instance
     */
    private $instance;


    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return RecentCollection
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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user
     *
     * @param \Entity\User $user
     * @return RecentCollection
     */
    public function setUser(?\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set collection
     *
     * @param \Entity\Collection $collection
     * @return RecentCollection
     */
    public function setCollection(?\Entity\Collection $collection = null)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Get collection
     *
     * @return \Entity\Collection 
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Set instance
     *
     * @param \Entity\Instance $instance
     * @return RecentCollection
     */
    public function setInstance(?\Entity\Instance $instance = null)
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * Get instance
     *
     * @return \Entity\Instance 
     */
    public function getInstance()
    {
        return $this->instance;
    }
}
