<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LTI13Issuer
 */
#[ORM\Table(name: 'lti13_issuers')]
#[ORM\Entity]
class LTI13Issuer
{
    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'createdAt', type: 'datetime', nullable: true)]
    private $createdAt;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'updatedAt', type: 'datetime', nullable: true, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private $updatedAt = 'CURRENT_TIMESTAMP';

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'host', type: 'string', nullable: true)]
    private $host;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'client_id', type: 'string', nullable: true)]
    private $client_id;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'auth_login_url', type: 'string', nullable: true)]
    private $auth_login_url;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'auth_token_url', type: 'string', nullable: true)]
    private $auth_token_url;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'key_set_url', type: 'string', nullable: true)]
    private $key_set_url;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'private_key', type: 'text', nullable: true)]
    private $private_key;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'kid', type: 'string', nullable: true)]
    private $kid;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'lti13_issuers_id_seq', allocationSize: 1, initialValue: 1)]
    private $id;



    /**
     * Set createdAt.
     *
     * @param \DateTime|null $createdAt
     *
     * @return LTI13Issuer
     */
    public function setCreatedAt($createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt.
     *
     * @param \DateTime|null $updatedAt
     *
     * @return LTI13Issuer
     */
    public function setUpdatedAt($updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return \DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set host.
     *
     * @param string|null $host
     *
     * @return LTI13Issuer
     */
    public function setHost($host = null)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get host.
     *
     * @return string|null
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set clientId.
     *
     * @param string|null $clientId
     *
     * @return LTI13Issuer
     */
    public function setClientId($clientId = null)
    {
        $this->client_id = $clientId;

        return $this;
    }

    /**
     * Get clientId.
     *
     * @return string|null
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * Set authLoginUrl.
     *
     * @param string|null $authLoginUrl
     *
     * @return LTI13Issuer
     */
    public function setAuthLoginUrl($authLoginUrl = null)
    {
        $this->auth_login_url = $authLoginUrl;

        return $this;
    }

    /**
     * Get authLoginUrl.
     *
     * @return string|null
     */
    public function getAuthLoginUrl()
    {
        return $this->auth_login_url;
    }

    /**
     * Set authTokenUrl.
     *
     * @param string|null $authTokenUrl
     *
     * @return LTI13Issuer
     */
    public function setAuthTokenUrl($authTokenUrl = null)
    {
        $this->auth_token_url = $authTokenUrl;

        return $this;
    }

    /**
     * Get authTokenUrl.
     *
     * @return string|null
     */
    public function getAuthTokenUrl()
    {
        return $this->auth_token_url;
    }

    /**
     * Set keySetUrl.
     *
     * @param string|null $keySetUrl
     *
     * @return LTI13Issuer
     */
    public function setKeySetUrl($keySetUrl = null)
    {
        $this->key_set_url = $keySetUrl;

        return $this;
    }

    /**
     * Get keySetUrl.
     *
     * @return string|null
     */
    public function getKeySetUrl()
    {
        return $this->key_set_url;
    }

    /**
     * Set privateKey.
     *
     * @param string|null $privateKey
     *
     * @return LTI13Issuer
     */
    public function setPrivateKey($privateKey = null)
    {
        $this->private_key = $privateKey;

        return $this;
    }

    /**
     * Get privateKey.
     *
     * @return string|null
     */
    public function getPrivateKey()
    {
        return $this->private_key;
    }

    /**
     * Set kid.
     *
     * @param string|null $kid
     *
     * @return LTI13Issuer
     */
    public function setKid($kid = null)
    {
        $this->kid = $kid;

        return $this;
    }

    /**
     * Get kid.
     *
     * @return string|null
     */
    public function getKid()
    {
        return $this->kid;
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
}
