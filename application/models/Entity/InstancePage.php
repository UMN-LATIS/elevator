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
    /**
     * @var integer
     */
    private $sortOrder;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;

    /**
     * @var \Entity\InstancePage
     */
    private $parent;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set sortOrder
     *
     * @param integer $sortOrder
     *
     * @return InstancePage
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * Get sortOrder
     *
     * @return integer
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * Add child
     *
     * @param \Entity\InstancePage $child
     *
     * @return InstancePage
     */
    public function addChild(\Entity\InstancePage $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \Entity\InstancePage $child
     */
    public function removeChild(\Entity\InstancePage $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param \Entity\InstancePage $parent
     *
     * @return InstancePage
     */
    public function setParent(\Entity\InstancePage $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Entity\InstancePage
     */
    public function getParent()
    {
        return $this->parent;
    }
}
