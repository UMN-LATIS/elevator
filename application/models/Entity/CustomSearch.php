<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CustomSearch
 *
 * @ORM\Table(name="custom_search")
 * @ORM\Entity
 */
class CustomSearch
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="searchConfig", type="text", nullable=true)
     */
    private $searchConfig;

    /**
     * @var string|null
     *
     * @ORM\Column(name="searchTitle", type="string", nullable=true)
     */
    private $searchTitle;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="custom_search_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

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
