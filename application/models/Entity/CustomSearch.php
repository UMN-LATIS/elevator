<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CustomSearch
 */
class CustomSearch
{
    /**
     * @var string
     */
    private $searchConfig;

    /**
     * @var string
     */
    private $searchTitle;

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
     * Set searchConfig
     *
     * @param string $searchConfig
     * @return CustomSearch
     */
    public function setSearchConfig($searchConfig)
    {
        $this->searchConfig = $searchConfig;

        return $this;
    }

    /**
     * Get searchConfig
     *
     * @return string 
     */
    public function getSearchConfig()
    {
        return $this->searchConfig;
    }

    /**
     * Set searchTitle
     *
     * @param string $searchTitle
     * @return CustomSearch
     */
    public function setSearchTitle($searchTitle)
    {
        $this->searchTitle = $searchTitle;

        return $this;
    }

    /**
     * Get searchTitle
     *
     * @return string 
     */
    public function getSearchTitle()
    {
        return $this->searchTitle;
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
     * @return CustomSearch
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
     * @return CustomSearch
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
