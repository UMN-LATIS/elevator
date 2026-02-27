<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DrawerPermission
 */
#[ORM\Table(name: 'drawer_permissions')]
#[ORM\Entity]
class DrawerPermission
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var \Entity\DrawerGroup
     */
    #[ORM\JoinColumn(name: 'drawer_group_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\DrawerGroup::class)]
    private $group;

    /**
     * @var \Entity\Drawer
     */
    #[ORM\JoinColumn(name: 'drawer_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\Drawer::class)]
    private $drawer;

    /**
     * @var \Entity\Permission
     */
    #[ORM\JoinColumn(name: 'permission_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\Permission::class)]
    private $permission;



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
     * Set group.
     *
     * @param \Entity\DrawerGroup|null $group
     *
     * @return DrawerPermission
     */
    public function setGroup(?\Entity\DrawerGroup $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group.
     *
     * @return \Entity\DrawerGroup|null
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set drawer.
     *
     * @param \Entity\Drawer|null $drawer
     *
     * @return DrawerPermission
     */
    public function setDrawer(?\Entity\Drawer $drawer = null)
    {
        $this->drawer = $drawer;

        return $this;
    }

    /**
     * Get drawer.
     *
     * @return \Entity\Drawer|null
     */
    public function getDrawer()
    {
        return $this->drawer;
    }

    /**
     * Set permission.
     *
     * @param \Entity\Permission|null $permission
     *
     * @return DrawerPermission
     */
    public function setPermission(?\Entity\Permission $permission = null)
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * Get permission.
     *
     * @return \Entity\Permission|null
     */
    public function getPermission()
    {
        return $this->permission;
    }
}
