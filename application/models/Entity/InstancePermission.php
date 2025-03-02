<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InstancePermission
 *
 * @ORM\Table(name="instance_permissions")
 * @ORM\Entity
 */
class InstancePermission
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="instance_permissions_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Entity\InstanceGroup
     *
     * @ORM\ManyToOne(targetEntity="Entity\InstanceGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     * })
     */
    private $group;

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
     * @var \Entity\Permission
     *
     * @ORM\ManyToOne(targetEntity="Entity\Permission")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="permission_id", referencedColumnName="id")
     * })
     */
    private $permission;


}
