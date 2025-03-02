<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Log
 *
 * @ORM\Table(name="logs")
 * @ORM\Entity
 */
class Log
{
    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var string|null
     *
     * @ORM\Column(name="asset", type="string", nullable=true)
     */
    private $asset;

    /**
     * @var string|null
     *
     * @ORM\Column(name="task", type="string", nullable=true)
     */
    private $task;

    /**
     * @var string|null
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var string|null
     *
     * @ORM\Column(name="url", type="text", nullable=true)
     */
    private $url;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="logs_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Entity\Instance
     *
     * @ORM\ManyToOne(targetEntity="Entity\Instance")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $instance;

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
     * @var \Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;


}
