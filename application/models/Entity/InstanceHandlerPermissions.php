<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InstanceHandlerPermissions
 *
 * @ORM\Table(name="instance_handler_permissions")
 * @ORM\Entity
 */
class InstanceHandlerPermissions
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="handler_name", type="string", nullable=true)
     */
    private $handler_name;

    /**
     * @var int|null
     *
     * @ORM\Column(name="permission_group", type="integer", nullable=true)
     */
    private $permission_group;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="instance_handler_permissions_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Entity\Instance
     *
     * @ORM\ManyToOne(targetEntity="Entity\Instance", inversedBy="handler_permissions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id")
     * })
     */
    private $instance;


}
