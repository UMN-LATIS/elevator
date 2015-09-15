<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InstancePermission
 */
class InstancePermission
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
     * @var \Entity\Instance
     */
    private $instance;

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
     * @return InstancePermission
     */
    public function setGroup(\Entity\InstanceGroup $group = null)
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
     * Set instance
     *
     * @param \Entity\Instance $instance
     * @return InstancePermission
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
     * Set permission
     *
     * @param \Entity\Permission $permission
     * @return InstancePermission
     */
    public function setPermission(\Entity\Permission $permission = null)
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
