<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DrawerPermission
 *
 * @ORM\Table(name="drawer_permissions")
 * @ORM\Entity
 */
class DrawerPermission
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="drawer_permissions_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Entity\DrawerGroup
     *
     * @ORM\ManyToOne(targetEntity="Entity\DrawerGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="drawer_group_id", referencedColumnName="id")
     * })
     */
    private $group;

    /**
     * @var \Entity\Drawer
     *
     * @ORM\ManyToOne(targetEntity="Entity\Drawer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="drawer_id", referencedColumnName="id")
     * })
     */
    private $drawer;

    /**
     * @var \Entity\Permission
     *
     * @ORM\ManyToOne(targetEntity="Entity\Permission")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="permission_id", referencedColumnName="id")
     * })
     */
    private $permission;


}
