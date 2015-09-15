<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InstancePage
 */
class InstancePage
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $body;

    /**
     * @var \DateTime
     */
    private $modifiedAt;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Entity\Instance
     */
    private $instance;


    /**
     * Set title
     *
     * @param string $title
     * @return InstancePage
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set body
     *
     * @param string $body
     * @return InstancePage
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string 
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set modifiedAt
     *
     * @param \DateTime $modifiedAt
     * @return InstancePage
     */
    public function setModifiedAt($modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * Get modifiedAt
     *
     * @return \DateTime 
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
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
     * Set instance
     *
     * @param \Entity\Instance $instance
     * @return InstancePage
     */
    public function setInstance(\Entity\Instance $instance = null)
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * Get instance
     *
     * @return \Entity\Instance 
     */
    public function getInstance()
    {
        return $this->instance;
    }
    /**
     * @var boolean
     */
    private $includeInHeader;


    /**
     * Set includeInHeader
     *
     * @param boolean $includeInHeader
     * @return InstancePage
     */
    public function setIncludeInHeader($includeInHeader)
    {
        $this->includeInHeader = $includeInHeader;

        return $this;
    }

    /**
     * Get includeInHeader
     *
     * @return boolean 
     */
    public function getIncludeInHeader()
    {
        return $this->includeInHeader;
    }
}
