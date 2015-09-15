<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DrawerGroup
 */
class DrawerGroup
{
    /**
     * @var string
     */
    private $group_type;

    /**
     * @var string
     */
    private $group_value;

    /**
     * @var string
     */
    private $group_label;

    /**
     * @var \DateTime
     */
    private $expiration;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $permissions;

    /**
     * @var \Entity\User
     */
    private $user;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $drawer;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $group_values;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->drawer = new \Doctrine\Common\Collections\ArrayCollection();
        $this->group_values = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set group_type
     *
     * @param string $groupType
     * @return DrawerGroup
     */
    public function setGroupType($groupType)
    {
        $this->group_type = $groupType;

        return $this;
    }

    /**
     * Get group_type
     *
     * @return string 
     */
    public function getGroupType()
    {
        return $this->group_type;
    }

    /**
     * Set group_value
     *
     * @param string $groupValue
     * @return DrawerGroup
     */
    public function setGroupValue($groupValue)
    {
        $this->group_value = $groupValue;

        return $this;
    }

    /**
     * Get group_value
     *
     * @return string 
     */
    public function getGroupValue()
    {
        return $this->group_value;
    }

    /**
     * Set group_label
     *
     * @param string $groupLabel
     * @return DrawerGroup
     */
    public function setGroupLabel($groupLabel)
    {
        $this->group_label = $groupLabel;

        return $this;
    }

    /**
     * Get group_label
     *
     * @return string 
     */
    public function getGroupLabel()
    {
        return $this->group_label;
    }

    /**
     * Set expiration
     *
     * @param \DateTime $expiration
     * @return DrawerGroup
     */
    public function setExpiration($expiration)
    {
        $this->expiration = $expiration;

        return $this;
    }

    /**
     * Get expiration
     *
     * @return \DateTime 
     */
    public function getExpiration()
    {
        return $this->expiration;
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
     * @return DrawerGroup
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
     * Set user
     *
     * @param \Entity\User $user
     * @return DrawerGroup
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
     * Add drawer
     *
     * @param \Entity\Drawer $drawer
     * @return DrawerGroup
     */
    public function addDrawer(\Entity\Drawer $drawer)
    {
        $this->drawer[] = $drawer;

        return $this;
    }

    /**
     * Remove drawer
     *
     * @param \Entity\Drawer $drawer
     */
    public function removeDrawer(\Entity\Drawer $drawer)
    {
        $this->drawer->removeElement($drawer);
    }

    /**
     * Get drawer
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDrawer()
    {
        return $this->drawer;
    }

    /**
     * Add group_values
     *
     * @param \Entity\GroupEntry $groupValues
     * @return DrawerGroup
     */
    public function addGroupValue(\Entity\GroupEntry $groupValues)
    {
        $this->group_values[] = $groupValues;

        return $this;
    }

    /**
     * Remove group_values
     *
     * @param \Entity\GroupEntry $groupValues
     */
    public function removeGroupValue(\Entity\GroupEntry $groupValues)
    {
        $this->group_values->removeElement($groupValues);
    }

    /**
     * Get group_values
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getGroupValues()
    {
        return $this->group_values;
    }
}
