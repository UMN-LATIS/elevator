<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Drawer
 */
class Drawer
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var boolean
     */
    private $changedSinceArchive;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $permissions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $recentDrawer;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $items;

    /**
     * @var \Entity\Instance
     */
    private $instance;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $groups;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->recentDrawer = new \Doctrine\Common\Collections\ArrayCollection();
        $this->items = new \Doctrine\Common\Collections\ArrayCollection();
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Drawer
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set changedSinceArchive
     *
     * @param boolean $changedSinceArchive
     * @return Drawer
     */
    public function setChangedSinceArchive($changedSinceArchive)
    {
        $this->changedSinceArchive = $changedSinceArchive;

        return $this;
    }

    /**
     * Get changedSinceArchive
     *
     * @return boolean 
     */
    public function getChangedSinceArchive()
    {
        return $this->changedSinceArchive;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Drawer
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
     * Add permissions
     *
     * @param \Entity\DrawerPermission $permissions
     * @return Drawer
     */
    public function addPermission(\Entity\DrawerPermission $permissions)
    {
        $this->permissions[] = $permissions;

        return $this;
    }

    /**
     * Remove permissions
     *
     * @param \Entity\DrawerPermission $permissions
     */
    public function removePermission(\Entity\DrawerPermission $permissions)
    {
        $this->permissions->removeElement($permissions);
    }

    /**
     * Get permissions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Add recentDrawer
     *
     * @param \Entity\RecentDrawer $recentDrawer
     * @return Drawer
     */
    public function addRecentDrawer(\Entity\RecentDrawer $recentDrawer)
    {
        $this->recentDrawer[] = $recentDrawer;

        return $this;
    }

    /**
     * Remove recentDrawer
     *
     * @param \Entity\RecentDrawer $recentDrawer
     */
    public function removeRecentDrawer(\Entity\RecentDrawer $recentDrawer)
    {
        $this->recentDrawer->removeElement($recentDrawer);
    }

    /**
     * Get recentDrawer
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRecentDrawer()
    {
        return $this->recentDrawer;
    }

    /**
     * Add items
     *
     * @param \Entity\DrawerItem $items
     * @return Drawer
     */
    public function addItem(\Entity\DrawerItem $items)
    {
        $this->items[] = $items;

        return $this;
    }

    /**
     * Remove items
     *
     * @param \Entity\DrawerItem $items
     */
    public function removeItem(\Entity\DrawerItem $items)
    {
        $this->items->removeElement($items);
    }

    /**
     * Get items
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Set instance
     *
     * @param \Entity\Instance $instance
     * @return Drawer
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
     * Add groups
     *
     * @param \Entity\DrawerGroup $groups
     * @return Drawer
     */
    public function addGroup(\Entity\DrawerGroup $groups)
    {
        $this->groups[] = $groups;

        return $this;
    }

    /**
     * Remove groups
     *
     * @param \Entity\DrawerGroup $groups
     */
    public function removeGroup(\Entity\DrawerGroup $groups)
    {
        $this->groups->removeElement($groups);
    }

    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getGroups()
    {
        return $this->groups;
    }
    /**
     * @var string
     */
    private $sortBy;


    /**
     * Set sortBy
     *
     * @param string $sortBy
     *
     * @return Drawer
     */
    public function setSortBy($sortBy)
    {
        $this->sortBy = $sortBy;

        return $this;
    }

    /**
     * Get sortBy
     *
     * @return string
     */
    public function getSortBy()
    {
        return $this->sortBy;
    }
}
