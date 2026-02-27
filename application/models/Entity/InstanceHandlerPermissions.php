<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InstanceHandlerPermissions
 */
#[ORM\Table(name: 'instance_handler_permissions')]
#[ORM\Entity]
class InstanceHandlerPermissions
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'handler_name', type: 'string', nullable: true)]
    private $handler_name;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'permission_group', type: 'integer', nullable: true)]
    private $permission_group;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var \Entity\Instance
     */
    #[ORM\JoinColumn(name: 'instance_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\Instance::class, inversedBy: 'handler_permissions')]
    private $instance;



    /**
     * Set handlerName.
     *
     * @param string|null $handlerName
     *
     * @return InstanceHandlerPermissions
     */
    public function setHandlerName($handlerName = null)
    {
        $this->handler_name = $handlerName;

        return $this;
    }

    /**
     * Get handlerName.
     *
     * @return string|null
     */
    public function getHandlerName()
    {
        return $this->handler_name;
    }

    /**
     * Set permissionGroup.
     *
     * @param int|null $permissionGroup
     *
     * @return InstanceHandlerPermissions
     */
    public function setPermissionGroup($permissionGroup = null)
    {
        $this->permission_group = $permissionGroup;

        return $this;
    }

    /**
     * Get permissionGroup.
     *
     * @return int|null
     */
    public function getPermissionGroup()
    {
        return $this->permission_group;
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
     * Set instance.
     *
     * @param \Entity\Instance|null $instance
     *
     * @return InstanceHandlerPermissions
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
}
