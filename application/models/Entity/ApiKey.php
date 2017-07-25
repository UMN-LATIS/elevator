<?php

namespace Entity;

/**
 * ApiKey
 */
class ApiKey
{

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $key;

    /**
     * @var boolean
     */
    private $read;

    /**
     * @var boolean
     */
    private $write;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Entity\User
     */
    private $owner;


    /**
     * Set label
     *
     * @param string $label
     *
     * @return ApiKey
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set key
     *
     * @param string $key
     *
     * @return ApiKey
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set read
     *
     * @param boolean $read
     *
     * @return ApiKey
     */
    public function setRead($read)
    {
        $this->read = $read;

        return $this;
    }

    /**
     * Get read
     *
     * @return boolean
     */
    public function getRead()
    {
        return $this->read;
    }

    /**
     * Set write
     *
     * @param boolean $write
     *
     * @return ApiKey
     */
    public function setWrite($write)
    {
        $this->write = $write;

        return $this;
    }

    /**
     * Get write
     *
     * @return boolean
     */
    public function getWrite()
    {
        return $this->write;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set owner
     *
     * @param \Entity\User $owner
     *
     * @return ApiKey
     */
    public function setOwner(\Entity\User $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \Entity\User
     */
    public function getOwner()
    {
        return $this->owner;
    }
    /**
     * @var string
     */
    private $apiKey;


    /**
     * Set apiKey
     *
     * @param string $apiKey
     *
     * @return ApiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Get apiKey
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }
    /**
     * @var boolean
     */
    private $readable;

    /**
     * @var boolean
     */
    private $writable;


    /**
     * Set readable
     *
     * @param boolean $readable
     *
     * @return ApiKey
     */
    public function setReadable($readable)
    {
        $this->readable = $readable;

        return $this;
    }

    /**
     * Get readable
     *
     * @return boolean
     */
    public function getReadable()
    {
        return $this->readable;
    }

    /**
     * Set writable
     *
     * @param boolean $writable
     *
     * @return ApiKey
     */
    public function setWritable($writable)
    {
        $this->writable = $writable;

        return $this;
    }

    /**
     * Get writable
     *
     * @return boolean
     */
    public function getWritable()
    {
        return $this->writable;
    }
    /**
     * @var boolean
     */
    private $allowsRead;

    /**
     * @var boolean
     */
    private $allowsWrite;


    /**
     * Set allowsRead
     *
     * @param boolean $allowsRead
     *
     * @return ApiKey
     */
    public function setAllowsRead($allowsRead)
    {
        $this->allowsRead = $allowsRead;

        return $this;
    }

    /**
     * Get allowsRead
     *
     * @return boolean
     */
    public function getAllowsRead()
    {
        return $this->allowsRead;
    }

    /**
     * Set allowsWrite
     *
     * @param boolean $allowsWrite
     *
     * @return ApiKey
     */
    public function setAllowsWrite($allowsWrite)
    {
        $this->allowsWrite = $allowsWrite;

        return $this;
    }

    /**
     * Get allowsWrite
     *
     * @return boolean
     */
    public function getAllowsWrite()
    {
        return $this->allowsWrite;
    }
    /**
     * @var string
     */
    private $apiSecret;


    /**
     * Set apiSecret
     *
     * @param string $apiSecret
     *
     * @return ApiKey
     */
    public function setApiSecret($apiSecret)
    {
        $this->apiSecret = $apiSecret;

        return $this;
    }

    /**
     * Get apiSecret
     *
     * @return string
     */
    public function getApiSecret()
    {
        return $this->apiSecret;
    }
    /**
     * @var boolean
     */
    private $systemAccount;


    /**
     * Set systemAccount
     *
     * @param boolean $systemAccount
     *
     * @return ApiKey
     */
    public function setSystemAccount($systemAccount)
    {
        $this->systemAccount = $systemAccount;

        return $this;
    }

    /**
     * Get systemAccount
     *
     * @return boolean
     */
    public function getSystemAccount()
    {
        return $this->systemAccount;
    }
}
