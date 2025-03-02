<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DrawerGroup
 *
 * @ORM\Table(name="drawer_groups")
 * @ORM\Entity
 */
class DrawerGroup
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="group_type", type="string", nullable=true)
     */
    private $group_type;

    /**
     * @var string|null
     *
     * @ORM\Column(name="group_value", type="string", nullable=true)
     */
    private $group_value;

    /**
     * @var string|null
     *
     * @ORM\Column(name="group_label", type="string", nullable=true)
     */
    private $group_label;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="expiration", type="datetime", nullable=true)
     */
    private $expiration;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="drawer_groups_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\DrawerPermission", mappedBy="group", cascade={"remove"})
     */
    private $permissions;

    /**
     * @var \Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Entity\Drawer")
     * @ORM\JoinTable(name="drawergroup_drawer",
     *   joinColumns={
     *     @ORM\JoinColumn(name="drawergroup_id", referencedColumnName="id", onDelete="CASCADE")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="drawer_id", referencedColumnName="id", onDelete="CASCADE")
     *   }
     * )
     */
    private $drawer = array();

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Entity\GroupEntry", inversedBy="group", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(name="drawergroup_groupentry",
     *   joinColumns={
     *     @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="entry_id", referencedColumnName="id", unique=true, onDelete="CASCADE")
     *   }
     * )
     */
    private $group_values = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->drawer = new \Doctrine\Common\Collections\ArrayCollection();
        $this->group_values = new \Doctrine\Common\Collections\ArrayCollection();
    }

}
