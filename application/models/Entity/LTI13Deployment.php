<?php

namespace Entity;

/**
 * LTI13Deployment
 */
class LTI13Deployment
{
    /**
     * @var \DateTime|null
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     */
    private $updatedAt;

    /**
     * @var string|null
     */
    private $deployment_id;

    /**
     * @var int
     */
    private $id;

    /**
     * @var \Entity\LTI13Issuer
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
    public function setIssuer(\Entity\LTI13Issuer $issuer = null)
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
