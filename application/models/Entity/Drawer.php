<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Drawer
 *
 * @ORM\Table(name="drawers")
 * @ORM\Entity
 */
class Drawer
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="title", type="string", nullable=true)
     */
    private $title;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="changedSinceArchive", type="boolean", nullable=true)
     */
    private $changedSinceArchive;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sortBy", type="string", nullable=true)
     */
    private $sortBy;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="drawers_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\DrawerPermission", mappedBy="drawer", cascade={"remove"})
     */
    private $permissions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\RecentDrawer", mappedBy="drawer", cascade={"remove"})
     */
    private $recentDrawer;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\DrawerItem", mappedBy="drawer", cascade={"remove"})
     * @ORM\OrderBy({
     *     "sortOrder"="ASC",
     *     "id"="ASC"
     * })
     */
    private $items;

    /**
     * @var \Entity\Instance
     *
     * @ORM\ManyToOne(targetEntity="Entity\Instance")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id")
     * })
     */
    private $instance;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Entity\DrawerGroup", mappedBy="drawer")
     */
    private $groups = array();

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
     * Set title.
     *
     * @param string|null $title
     *
     * @return Drawer
     */
    public function setTitle($title = null)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set changedSinceArchive.
     *
     * @param bool|null $changedSinceArchive
     *
     * @return Drawer
     */
    public function setChangedSinceArchive($changedSinceArchive = null)
    {
        $this->changedSinceArchive = $changedSinceArchive;

        return $this;
    }

    /**
     * Get changedSinceArchive.
     *
     * @return bool|null
     */
    public function getChangedSinceArchive()
    {
        return $this->changedSinceArchive;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime|null $createdAt
     *
     * @return Drawer
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
     * Set sortBy.
     *
     * @param string|null $sortBy
     *
     * @return Drawer
     */
    public function setSortBy($sortBy = null)
    {
        $this->sortBy = $sortBy;

        return $this;
    }

    /**
     * Get sortBy.
     *
     * @return string|null
     */
    public function getSortBy()
    {
        return $this->sortBy;
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
     * Add permission.
     *
     * @param \Entity\DrawerPermission $permission
     *
     * @return Drawer
     */
    public function addPermission(\Entity\DrawerPermission $permission)
    {
        $this->permissions[] = $permission;

        return $this;
    }

    /**
     * Remove permission.
     *
     * @param \Entity\DrawerPermission $permission
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePermission(\Entity\DrawerPermission $permission)
    {
        return $this->permissions->removeElement($permission);
    }

    /**
     * Get permissions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Add recentDrawer.
     *
     * @param \Entity\RecentDrawer $recentDrawer
     *
     * @return Drawer
     */
    public function addRecentDrawer(\Entity\RecentDrawer $recentDrawer)
    {
        $this->recentDrawer[] = $recentDrawer;

        return $this;
    }

    /**
     * Remove recentDrawer.
     *
     * @param \Entity\RecentDrawer $recentDrawer
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeRecentDrawer(\Entity\RecentDrawer $recentDrawer)
    {
        return $this->recentDrawer->removeElement($recentDrawer);
    }

    /**
     * Get recentDrawer.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecentDrawer()
    {
        return $this->recentDrawer;
    }

    /**
     * Add item.
     *
     * @param \Entity\DrawerItem $item
     *
     * @return Drawer
     */
    public function addItem(\Entity\DrawerItem $item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Remove item.
     *
     * @param \Entity\DrawerItem $item
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeItem(\Entity\DrawerItem $item)
    {
        return $this->items->removeElement($item);
    }

    /**
     * Get items.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Set instance.
     *
     * @param \Entity\Instance|null $instance
     *
     * @return Drawer
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
     * Add group.
     *
     * @param \Entity\DrawerGroup $group
     *
     * @return Drawer
     */
    public function addGroup(\Entity\DrawerGroup $group)
    {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * Remove group.
     *
     * @param \Entity\DrawerGroup $group
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeGroup(\Entity\DrawerGroup $group)
    {
        return $this->groups->removeElement($group);
    }

    /**
     * Get groups.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }
}
