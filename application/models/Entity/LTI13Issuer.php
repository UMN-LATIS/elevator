<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LTI13Issuer
 *
 * @ORM\Table(name="lti13_issuers")
 * @ORM\Entity
 */
class LTI13Issuer
{
    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="updatedAt", type="datetime", nullable=true, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $updatedAt = 'CURRENT_TIMESTAMP';

    /**
     * @var string|null
     *
     * @ORM\Column(name="host", type="string", nullable=true)
     */
    private $host;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_id", type="string", nullable=true)
     */
    private $client_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="auth_login_url", type="string", nullable=true)
     */
    private $auth_login_url;

    /**
     * @var string|null
     *
     * @ORM\Column(name="auth_token_url", type="string", nullable=true)
     */
    private $auth_token_url;

    /**
     * @var string|null
     *
     * @ORM\Column(name="key_set_url", type="string", nullable=true)
     */
    private $key_set_url;

    /**
     * @var string|null
     *
     * @ORM\Column(name="private_key", type="text", nullable=true)
     */
    private $private_key;

    /**
     * @var string|null
     *
     * @ORM\Column(name="kid", type="string", nullable=true)
     */
    private $kid;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="lti13_issuers_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;


}
