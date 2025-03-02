<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ApiKey
 */
#[ORM\Table(name: 'api_keys')]
#[ORM\Index(name: 0, columns: ['apiKey'])]
#[ORM\Entity]
class ApiKey
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'label', type: 'string', nullable: true)]
    private $label;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'apiKey', type: 'string', nullable: true)]
    private $apiKey;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'apiSecret', type: 'string', nullable: true)]
    private $apiSecret;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'allowsRead', type: 'boolean', nullable: true)]
    private $allowsRead;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'allowsWrite', type: 'boolean', nullable: true)]
    private $allowsWrite;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'systemAccount', type: 'boolean', nullable: true)]
    private $systemAccount;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'api_keys_id_seq', allocationSize: 1, initialValue: 1)]
    private $id;

    /**
     * @var \Entity\User
     */
    #[ORM\JoinColumn(name: 'owner', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\User::class)]
    private $owner;



    /**
     * Set label.
     *
     * @param string|null $label
     *
     * @return ApiKey
     */
    public function setLabel($label = null)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label.
     *
     * @return string|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set apiKey.
     *
     * @param string|null $apiKey
     *
     * @return ApiKey
     */
    public function setApiKey($apiKey = null)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Get apiKey.
     *
     * @return string|null
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set apiSecret.
     *
     * @param string|null $apiSecret
     *
     * @return ApiKey
     */
    public function setApiSecret($apiSecret = null)
    {
        $this->apiSecret = $apiSecret;

        return $this;
    }

    /**
     * Get apiSecret.
     *
     * @return string|null
     */
    public function getApiSecret()
    {
        return $this->apiSecret;
    }

    /**
     * Set allowsRead.
     *
     * @param bool|null $allowsRead
     *
     * @return ApiKey
     */
    public function setAllowsRead($allowsRead = null)
    {
        $this->allowsRead = $allowsRead;

        return $this;
    }

    /**
     * Get allowsRead.
     *
     * @return bool|null
     */
    public function getAllowsRead()
    {
        return $this->allowsRead;
    }

    /**
     * Set allowsWrite.
     *
     * @param bool|null $allowsWrite
     *
     * @return ApiKey
     */
    public function setAllowsWrite($allowsWrite = null)
    {
        $this->allowsWrite = $allowsWrite;

        return $this;
    }

    /**
     * Get allowsWrite.
     *
     * @return bool|null
     */
    public function getAllowsWrite()
    {
        return $this->allowsWrite;
    }

    /**
     * Set systemAccount.
     *
     * @param bool|null $systemAccount
     *
     * @return ApiKey
     */
    public function setSystemAccount($systemAccount = null)
    {
        $this->systemAccount = $systemAccount;

        return $this;
    }

    /**
     * Get systemAccount.
     *
     * @return bool|null
     */
    public function getSystemAccount()
    {
        return $this->systemAccount;
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
     * Set owner.
     *
     * @param \Entity\User|null $owner
     *
     * @return ApiKey
     */
    public function setOwner(?\Entity\User $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner.
     *
     * @return \Entity\User|null
     */
    public function getOwner()
    {
        return $this->owner;
    }
}
