<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InstancePermission
 */
#[ORM\Table(name: 'instance_permissions')]
#[ORM\Entity]
class InstancePermission
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'instance_permissions_id_seq', allocationSize: 1, initialValue: 1)]
    private $id;

    /**
     * @var \Entity\InstanceGroup
     */
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\InstanceGroup::class)]
    private $group;

    /**
     * @var \Entity\Instance
     */
    #[ORM\JoinColumn(name: 'instance_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\Instance::class)]
    private $instance;

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
     * @param \Entity\InstanceGroup|null $group
     *
     * @return InstancePermission
     */
    public function setGroup(?\Entity\InstanceGroup $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group.
     *
     * @return \Entity\InstanceGroup|null
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set instance.
     *
     * @param \Entity\Instance|null $instance
     *
     * @return InstancePermission
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
     * Set permission.
     *
     * @param \Entity\Permission|null $permission
     *
     * @return InstancePermission
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
