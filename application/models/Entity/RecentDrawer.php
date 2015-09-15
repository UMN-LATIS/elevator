<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RecentDrawer
 */
class RecentDrawer
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
     * @var \Entity\Drawer
     */
    private $drawer;

    /**
     * @var \Entity\Instance
     */
    private $instance;


    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return RecentDrawer
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
     * @return RecentDrawer
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
     * Set drawer
     *
     * @param \Entity\Drawer $drawer
     * @return RecentDrawer
     */
    public function setDrawer(\Entity\Drawer $drawer = null)
    {
        $this->drawer = $drawer;

        return $this;
    }

    /**
     * Get drawer
     *
     * @return \Entity\Drawer 
     */
    public function getDrawer()
    {
        return $this->drawer;
    }

    /**
     * Set instance
     *
     * @param \Entity\Instance $instance
     * @return RecentDrawer
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
