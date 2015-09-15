<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InstanceHandlerPermissions
 */
class InstanceHandlerPermissions
{
    /**
     * @var string
     */
    private $handler_name;

    /**
     * @var integer
     */
    private $permission_group;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Entity\Instance
     */
    private $instance;


    /**
     * Set handler_name
     *
     * @param string $handlerName
     * @return InstanceHandlerPermissions
     */
    public function setHandlerName($handlerName)
    {
        $this->handler_name = $handlerName;

        return $this;
    }

    /**
     * Get handler_name
     *
     * @return string 
     */
    public function getHandlerName()
    {
        return $this->handler_name;
    }

    /**
     * Set permission_group
     *
     * @param integer $permissionGroup
     * @return InstanceHandlerPermissions
     */
    public function setPermissionGroup($permissionGroup)
    {
        $this->permission_group = $permissionGroup;

        return $this;
    }

    /**
     * Get permission_group
     *
     * @return integer 
     */
    public function getPermissionGroup()
    {
        return $this->permission_group;
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
     * Set instance
     *
     * @param \Entity\Instance $instance
     * @return InstanceHandlerPermissions
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
