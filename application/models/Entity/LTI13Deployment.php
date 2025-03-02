<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LTI13Deployment
 *
 * @ORM\Table(name="lti13_deployments")
 * @ORM\Entity
 */
class LTI13Deployment
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
     * @ORM\Column(name="deployment_id", type="string", nullable=true)
     */
    private $deployment_id;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="lti13_deployments_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Entity\LTI13Issuer
     *
     * @ORM\ManyToOne(targetEntity="Entity\LTI13Issuer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="issuer_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $issuer;


}
