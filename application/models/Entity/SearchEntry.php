<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SearchEntry
 *
 * @ORM\Table(name="searches")
 * @ORM\Entity
 */
class SearchEntry
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="searchText", type="text", nullable=true)
     */
    private $searchText;

    /**
     * @var array|null
     *
     * @ORM\Column(name="searchData", type="json_array", nullable=true)
     */
    private $searchData;

    /**
     * @var bool
     *
     * @ORM\Column(name="userInitiated", type="boolean", nullable=false)
     */
    private $userInitiated;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
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
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $instance;


}
