<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DrawerPermission
 */
class DrawerPermission
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Entity\DrawerGroup
     */
    private $group;

    /**
     * @var \Entity\Drawer
     */
    private $drawer;

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
     * @param \Entity\DrawerGroup $group
     * @return DrawerPermission
     */
    public function setGroup(? \Entity\DrawerGroup $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group
     *
     * @return \Entity\DrawerGroup 
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set drawer
     *
     * @param \Entity\Drawer $drawer
     * @return DrawerPermission
     */
    public function setDrawer(? \Entity\Drawer $drawer = null)
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
     * Set permission
     *
     * @param \Entity\Permission $permission
     * @return DrawerPermission
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
