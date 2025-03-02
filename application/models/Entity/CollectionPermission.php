<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CollectionPermission
 *
 * @ORM\Table(name="collection_permissions")
 * @ORM\Entity
 */
class CollectionPermission
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="collection_permissions_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Entity\InstanceGroup
     *
     * @ORM\ManyToOne(targetEntity="Entity\InstanceGroup", inversedBy="collection_permissions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     * })
     */
    private $group;

    /**
     * @var \Entity\Collection
     *
     * @ORM\ManyToOne(targetEntity="Entity\Collection", inversedBy="collection_permissions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="collection_id", referencedColumnName="id")
     * })
     */
    private $collection;

    /**
     * @var \Entity\Permission
     *
     * @ORM\ManyToOne(targetEntity="Entity\Permission", inversedBy="collection_permissions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="permission_id", referencedColumnName="id")
     * })
     */
    private $permission;


}
