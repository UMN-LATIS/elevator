<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ApiKey
 *
 * @ORM\Table(name="api_keys", indexes={@ORM\Index(name="0", columns={"apiKey"})})
 * @ORM\Entity
 */
class ApiKey
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="label", type="string", nullable=true)
     */
    private $label;

    /**
     * @var string|null
     *
     * @ORM\Column(name="apiKey", type="string", nullable=true)
     */
    private $apiKey;

    /**
     * @var string|null
     *
     * @ORM\Column(name="apiSecret", type="string", nullable=true)
     */
    private $apiSecret;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="allowsRead", type="boolean", nullable=true)
     */
    private $allowsRead;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="allowsWrite", type="boolean", nullable=true)
     */
    private $allowsWrite;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="systemAccount", type="boolean", nullable=true)
     */
    private $systemAccount;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="api_keys_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="owner", referencedColumnName="id")
     * })
     */
    private $owner;


}
