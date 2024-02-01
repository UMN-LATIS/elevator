<?php

namespace Entity;

/**
 * LTI13ResourceLink
 */
class LTI13ResourceLink
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
    private $resource_link;

    /**
     * @var string|null
     */
    private $created_line_item;

    /**
     * @var array|null
     */
    private $endpoint;

    /**
     * @var int
     */
    private $id;

    /**
     * @var \Entity\LTI13Deployment
     */
    private $deployment;


    /**
     * Set createdAt.
     *
     * @param \DateTime|null $createdAt
     *
     * @return LTI13ResourceLink
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
     * @return LTI13ResourceLink
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
     * Set resourceLink.
     *
     * @param string|null $resourceLink
     *
     * @return LTI13ResourceLink
     */
    public function setResourceLink($resourceLink = null)
    {
        $this->resource_link = $resourceLink;

        return $this;
    }

    /**
     * Get resourceLink.
     *
     * @return string|null
     */
    public function getResourceLink()
    {
        return $this->resource_link;
    }

    /**
     * Set createdLineItem.
     *
     * @param string|null $createdLineItem
     *
     * @return LTI13ResourceLink
     */
    public function setCreatedLineItem($createdLineItem = null)
    {
        $this->created_line_item = $createdLineItem;

        return $this;
    }

    /**
     * Get createdLineItem.
     *
     * @return string|null
     */
    public function getCreatedLineItem()
    {
        return $this->created_line_item;
    }

    /**
     * Set endpoint.
     *
     * @param array|null $endpoint
     *
     * @return LTI13ResourceLink
     */
    public function setEndpoint($endpoint = null)
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * Get endpoint.
     *
     * @return array|null
     */
    public function getEndpoint()
    {
        return $this->endpoint;
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
     * Set deployment.
     *
     * @param \Entity\LTI13Deployment|null $deployment
     *
     * @return LTI13ResourceLink
     */
    public function setDeployment(\Entity\LTI13Deployment $deployment = null)
    {
        $this->deployment = $deployment;

        return $this;
    }

    /**
     * Get deployment.
     *
     * @return \Entity\LTI13Deployment|null
     */
    public function getDeployment()
    {
        return $this->deployment;
    }
}
