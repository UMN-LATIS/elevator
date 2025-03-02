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



    /**
     * Set createdAt.
     *
     * @param \DateTime|null $createdAt
     *
     * @return LTI13Deployment
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
     * @return LTI13Deployment
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
     * Set deploymentId.
     *
     * @param string|null $deploymentId
     *
     * @return LTI13Deployment
     */
    public function setDeploymentId($deploymentId = null)
    {
        $this->deployment_id = $deploymentId;

        return $this;
    }

    /**
     * Get deploymentId.
     *
     * @return string|null
     */
    public function getDeploymentId()
    {
        return $this->deployment_id;
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
     * Set issuer.
     *
     * @param \Entity\LTI13Issuer|null $issuer
     *
     * @return LTI13Deployment
     */
    public function setIssuer(?\Entity\LTI13Issuer $issuer = null)
    {
        $this->issuer = $issuer;

        return $this;
    }

    /**
     * Get issuer.
     *
     * @return \Entity\LTI13Issuer|null
     */
    public function getIssuer()
    {
        return $this->issuer;
    }
}
