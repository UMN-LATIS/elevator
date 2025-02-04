<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CollectionPermission
 */
class CollectionPermission
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Entity\InstanceGroup
     */
    private $group;

    /**
     * @var \Entity\Collection
     */
    private $collection;

    /**
     * @var \Entity\Permission
     */
    private $permission;


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
     * Set group
     *
     * @param \Entity\InstanceGroup $group
     * @return CollectionPermission
     */
    public function setGroup(? \Entity\InstanceGroup $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group
     *
     * @return \Entity\InstanceGroup 
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set collection
     *
     * @param \Entity\Collection $collection
     * @return CollectionPermission
     */
    public function setCollection(? \Entity\Collection $collection = null)
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
     * Set permission
     *
     * @param \Entity\Permission $permission
     * @return CollectionPermission
     */
    public function setPermission(? \Entity\Permission $permission = null)
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * Get permission
     *
     * @return \Entity\Permission 
     */
    public function getPermission()
    {
        return $this->permission;
    }
}
