<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InstanceGroup
 *
 * @ORM\Table(name="instance_groups", indexes={@ORM\Index(name="0", columns={"group_type"}), @ORM\Index(name="1", columns={"group_value"})})
 * @ORM\Entity
 */
class InstanceGroup
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
     * @ORM\SequenceGenerator(sequenceName="instance_groups_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\InstancePermission", mappedBy="group", cascade={"remove"})
     */
    private $instance_permissions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\CollectionPermission", mappedBy="group", cascade={"remove"})
     */
    private $collection_permissions;

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
     * @ORM\ManyToMany(targetEntity="Entity\GroupEntry", inversedBy="group", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(name="instancegroup_groupentry",
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
        $this->instance_permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->collection_permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->group_values = new \Doctrine\Common\Collections\ArrayCollection();
    }

}
