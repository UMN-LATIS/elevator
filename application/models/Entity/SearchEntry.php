<?php

namespace Entity;

/**
 * SearchEntry
 */
class SearchEntry
{
    /**
     * @var string
     */
    private $searchText;

    /**
     * @var array
     */
    private $searchData;

    /**
     * @var boolean
     */
    private $userInitiated;

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
     * Set searchText
     *
     * @param string $searchText
     *
     * @return SearchEntry
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
     * Set searchData
     *
     * @param array $searchData
     *
     * @return SearchEntry
     */
    public function setSearchData($searchData)
    {
        $this->searchData = $searchData;

        return $this;
    }

    /**
     * Get searchData
     *
     * @return array
     */
    public function getSearchData()
    {
        return $this->searchData;
    }

    /**
     * Set userInitiated
     *
     * @param boolean $userInitiated
     *
     * @return SearchEntry
     */
    public function setUserInitiated($userInitiated)
    {
        $this->userInitiated = $userInitiated;

        return $this;
    }

    /**
     * Get userInitiated
     *
     * @return boolean
     */
    public function getUserInitiated()
    {
        return $this->userInitiated;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return SearchEntry
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
     *
     * @return SearchEntry
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
     *
     * @return SearchEntry
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

    /**
     * Set id
     *
     * @param guid $id
     *
     * @return SearchEntry
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
    /**
     * @var guid
     */
    private $searchId;


    /**
     * Set searchId
     *
     * @param guid $searchId
     *
     * @return SearchEntry
     */
    public function setSearchId($searchId)
    {
        $this->searchId = $searchId;

        return $this;
    }

    /**
     * Get searchId
     *
     * @return guid
     */
    public function getSearchId()
    {
        return $this->searchId;
    }
}
