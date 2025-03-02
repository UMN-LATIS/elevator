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


    /**
     * Set groupType.
     *
     * @param string|null $groupType
     *
     * @return InstanceGroup
     */
    public function setGroupType($groupType = null)
    {
        $this->group_type = $groupType;

        return $this;
    }

    /**
     * Get groupType.
     *
     * @return string|null
     */
    public function getGroupType()
    {
        return $this->group_type;
    }

    /**
     * Set groupValue.
     *
     * @param string|null $groupValue
     *
     * @return InstanceGroup
     */
    public function setGroupValue($groupValue = null)
    {
        $this->group_value = $groupValue;

        return $this;
    }

    /**
     * Get groupValue.
     *
     * @return string|null
     */
    public function getGroupValue()
    {
        return $this->group_value;
    }

    /**
     * Set groupLabel.
     *
     * @param string|null $groupLabel
     *
     * @return InstanceGroup
     */
    public function setGroupLabel($groupLabel = null)
    {
        $this->group_label = $groupLabel;

        return $this;
    }

    /**
     * Get groupLabel.
     *
     * @return string|null
     */
    public function getGroupLabel()
    {
        return $this->group_label;
    }

    /**
     * Set expiration.
     *
     * @param \DateTime|null $expiration
     *
     * @return InstanceGroup
     */
    public function setExpiration($expiration = null)
    {
        $this->expiration = $expiration;

        return $this;
    }

    /**
     * Get expiration.
     *
     * @return \DateTime|null
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

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
     * Add instancePermission.
     *
     * @param \Entity\InstancePermission $instancePermission
     *
     * @return InstanceGroup
     */
    public function addInstancePermission(\Entity\InstancePermission $instancePermission)
    {
        $this->instance_permissions[] = $instancePermission;

        return $this;
    }

    /**
     * Remove instancePermission.
     *
     * @param \Entity\InstancePermission $instancePermission
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeInstancePermission(\Entity\InstancePermission $instancePermission)
    {
        return $this->instance_permissions->removeElement($instancePermission);
    }

    /**
     * Get instancePermissions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInstancePermissions()
    {
        return $this->instance_permissions;
    }

    /**
     * Add collectionPermission.
     *
     * @param \Entity\CollectionPermission $collectionPermission
     *
     * @return InstanceGroup
     */
    public function addCollectionPermission(\Entity\CollectionPermission $collectionPermission)
    {
        $this->collection_permissions[] = $collectionPermission;

        return $this;
    }

    /**
     * Remove collectionPermission.
     *
     * @param \Entity\CollectionPermission $collectionPermission
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCollectionPermission(\Entity\CollectionPermission $collectionPermission)
    {
        return $this->collection_permissions->removeElement($collectionPermission);
    }

    /**
     * Get collectionPermissions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCollectionPermissions()
    {
        return $this->collection_permissions;
    }

    /**
     * Set instance.
     *
     * @param \Entity\Instance|null $instance
     *
     * @return InstanceGroup
     */
    public function setInstance(?\Entity\Instance $instance = null)
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * Get instance.
     *
     * @return \Entity\Instance|null
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * Add groupValue.
     *
     * @param \Entity\GroupEntry $groupValue
     *
     * @return InstanceGroup
     */
    public function addGroupValue(\Entity\GroupEntry $groupValue)
    {
        $this->group_values[] = $groupValue;

        return $this;
    }

    /**
     * Remove groupValue.
     *
     * @param \Entity\GroupEntry $groupValue
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeGroupValue(\Entity\GroupEntry $groupValue)
    {
        return $this->group_values->removeElement($groupValue);
    }

    /**
     * Get groupValues.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroupValues()
    {
        return $this->group_values;
    }
}
