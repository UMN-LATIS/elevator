<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RecentCollection
 *
 * @ORM\Table(name="recent_collection")
 * @ORM\Entity
 */
class RecentCollection
{
    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="recent_collection_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Entity\User", inversedBy="recent_collections")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

    /**
     * @var \Entity\Collection
     *
     * @ORM\ManyToOne(targetEntity="Entity\Collection")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="collection_id", referencedColumnName="id")
     * })
     */
    private $collection;

    /**
     * @var \Entity\Instance
     *
     * @ORM\ManyToOne(targetEntity="Entity\Instance")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id")
     * })
     */
    private $instance;


}
