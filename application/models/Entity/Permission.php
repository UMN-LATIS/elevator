<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Permission
 *
 * @ORM\Table(name="permissions")
 * @ORM\Entity
 */
class Permission
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", nullable=true)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="label", type="string", nullable=true)
     */
    private $label;

    /**
     * @var string|null
     *
     * @ORM\Column(name="level", type="string", nullable=true)
     */
    private $level;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="modifiedAt", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="permissions_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\InstancePermission", mappedBy="permission")
     */
    private $instances;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->instances = new \Doctrine\Common\Collections\ArrayCollection();
    }

}
