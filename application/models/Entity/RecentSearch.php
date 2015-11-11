<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RecentSearch
 */
class RecentSearch
{
    /**
     * @var string
     */
    private $searchId;

    /**
     * @var string
     */
    private $searchText;

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
     * @var \Entity\Instance
     */
    private $instance;


    /**
     * Set searchId
     *
     * @param string $searchId
     * @return RecentSearch
     */
    public function setSearchId($searchId)
    {
        $this->searchId = $searchId;

        return $this;
    }

    /**
     * Get searchId
     *
     * @return string 
     */
    public function getSearchId()
    {
        return $this->searchId;
    }

    /**
     * Set searchText
     *
     * @param string $searchText
     * @return RecentSearch
     */
    public function setSearchText($searchText)
    {
        $this->searchText = $searchText;

        return $this;
    }

    /**
     * Get searchText
     *
     * @return string 
     */
    public function getSearchText()
    {
        return $this->searchText;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return RecentSearch
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
     * @return RecentSearch
     */
    public function setUser(\Entity\User $user = null)
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
     * Set instance
     *
     * @param \Entity\Instance $instance
     * @return RecentSearch
     */
    public function setInstance(\Entity\Instance $instance = null)
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
