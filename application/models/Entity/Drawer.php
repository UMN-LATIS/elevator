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

}
