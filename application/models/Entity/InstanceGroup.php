<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InstanceGroup
 */
class InstanceGroup
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
    private $instance_permissions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $collection_permissions;

    /**
     * @var \Entity\Instance
     */
    private $instance;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $group_values;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->instance_permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->collection_permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->group_values = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set group_type
     *
     * @param string $groupType
     * @return InstanceGroup
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
     * @return InstanceGroup
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
     * @return InstanceGroup
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
     * @return InstanceGroup
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
     * Add instance_permissions
     *
     * @param \Entity\InstancePermission $instancePermissions
     * @return InstanceGroup
     */
    public function addInstancePermission(\Entity\InstancePermission $instancePermissions)
    {
        $this->instance_permissions[] = $instancePermissions;

        return $this;
    }

    /**
     * Remove instance_permissions
     *
     * @param \Entity\InstancePermission $instancePermissions
     */
    public function removeInstancePermission(\Entity\InstancePermission $instancePermissions)
    {
        $this->instance_permissions->removeElement($instancePermissions);
    }

    /**
     * Get instance_permissions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getInstancePermissions()
    {
        return $this->instance_permissions;
    }

    /**
     * Add collection_permissions
     *
     * @param \Entity\CollectionPermission $collectionPermissions
     * @return InstanceGroup
     */
    public function addCollectionPermission(\Entity\CollectionPermission $collectionPermissions)
    {
        $this->collection_permissions[] = $collectionPermissions;

        return $this;
    }

    /**
     * Remove collection_permissions
     *
     * @param \Entity\CollectionPermission $collectionPermissions
     */
    public function removeCollectionPermission(\Entity\CollectionPermission $collectionPermissions)
    {
        $this->collection_permissions->removeElement($collectionPermissions);
    }

    /**
     * Get collection_permissions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCollectionPermissions()
    {
        return $this->collection_permissions;
    }

    /**
     * Set instance
     *
     * @param \Entity\Instance $instance
     * @return InstanceGroup
     */
    public function setInstance(? \Entity\Instance $instance = null)
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
     * Add group_values
     *
     * @param \Entity\GroupEntry $groupValues
     * @return InstanceGroup
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
