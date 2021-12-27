<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Template
 */
class Template
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $modifiedAt;

    /**
     * @var boolean
     */
    private $includeInSearch = true;

    /**
     * @var boolean
     */
    private $indexForSearching = true;

    /**
     * @var integer
     */
    private $templateColor;

    /**
     * @var boolean
     */
    private $isHidden;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $widgets;

    /**
     * @var \Entity\Template
     */
    private $source_template;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $instances;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->widgets = new \Doctrine\Common\Collections\ArrayCollection();
        $this->instances = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Template
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Template
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set modifiedAt
     *
     * @param \DateTime $modifiedAt
     * @return Template
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
     * Set includeInSearch
     *
     * @param boolean $includeInSearch
     * @return Template
     */
    public function setIncludeInSearch($includeInSearch)
    {
        $this->includeInSearch = $includeInSearch;

        return $this;
    }

    /**
     * Get includeInSearch
     *
     * @return boolean
     */
    public function getIncludeInSearch()
    {
        return $this->includeInSearch;
    }

    /**
     * Set indexForSearching
     *
     * @param boolean $indexForSearching
     * @return Template
     */
    public function setIndexForSearching($indexForSearching)
    {
        $this->indexForSearching = $indexForSearching;

        return $this;
    }

    /**
     * Get indexForSearching
     *
     * @return boolean
     */
    public function getIndexForSearching()
    {
        return $this->indexForSearching;
    }

    /**
     * Set templateColor
     *
     * @param integer $templateColor
     * @return Template
     */
    public function setTemplateColor($templateColor)
    {
        $this->templateColor = $templateColor;

        return $this;
    }

    /**
     * Get templateColor
     *
     * @return integer
     */
    public function getTemplateColor()
    {
        return $this->templateColor;
    }

    /**
     * Set isHidden
     *
     * @param boolean $isHidden
     * @return Template
     */
    public function setIsHidden($isHidden)
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    /**
     * Get isHidden
     *
     * @return boolean
     */
    public function getIsHidden()
    {
        return $this->isHidden;
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
     * Add widgets
     *
     * @param \Entity\Widget $widgets
     * @return Template
     */
    public function addWidget(\Entity\Widget $widgets)
    {
        $this->widgets[] = $widgets;

        return $this;
    }

    /**
     * Remove widgets
     *
     * @param \Entity\Widget $widgets
     */
    public function removeWidget(\Entity\Widget $widgets)
    {
        $this->widgets->removeElement($widgets);
    }

    /**
     * Get widgets
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getWidgets()
    {
        return $this->widgets;
    }

    /**
     * Set source_template
     *
     * @param \Entity\Template $sourceTemplate
     * @return Template
     */
    public function setSourceTemplate(\Entity\Template $sourceTemplate = null)
    {
        $this->source_template = $sourceTemplate;

        return $this;
    }

    /**
     * Get source_template
     *
     * @return \Entity\Template
     */
    public function getSourceTemplate()
    {
        return $this->source_template;
    }

    /**
     * Add instances
     *
     * @param \Entity\Instance $instances
     * @return Template
     */
    public function addInstance(\Entity\Instance $instances)
    {
        $instances->addTemplate($this);
        $this->instances[] = $instances;

        return $this;
    }

    /**
     * Remove instances
     *
     * @param \Entity\Instance $instances
     */
    public function removeInstance(\Entity\Instance $instances)
    {
        $this->instances->removeElement($instances);
    }

    /**
     * Get instances
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInstances()
    {
        return $this->instances;
    }
    /**
     * @var boolean
     */
    private $showCollection;


    /**
     * Set showCollection
     *
     * @param boolean $showCollection
     *
     * @return Template
     */
    public function setShowCollection($showCollection)
    {
        $this->showCollection = $showCollection;

        return $this;
    }

    /**
     * Get showCollection
     *
     * @return boolean
     */
    public function getShowCollection()
    {
        return $this->showCollection;
    }
    /**
     * @var bool
     */
    private $showTemplate = '0';

    /**
     * @var bool
     */
    private $showTemplateInBrowse = '0';


    /**
     * Set showTemplate.
     *
     * @param bool $showTemplate
     *
     * @return Template
     */
    public function setShowTemplate($showTemplate)
    {
        $this->showTemplate = $showTemplate;

        return $this;
    }

    /**
     * Get showTemplate.
     *
     * @return bool
     */
    public function getShowTemplate()
    {
        return $this->showTemplate;
    }

    /**
     * Set showTemplateInBrowse.
     *
     * @param bool $showTemplateInBrowse
     *
     * @return Template
     */
    public function setShowTemplateInBrowse($showTemplateInBrowse)
    {
        $this->showTemplateInBrowse = $showTemplateInBrowse;

        return $this;
    }

    /**
     * Get showTemplateInBrowse.
     *
     * @return bool
     */
    public function getShowTemplateInBrowse()
    {
        return $this->showTemplateInBrowse;
    }
    /**
     * @var int
     */
    private $collectionPosition = '1';

    /**
     * @var int
     */
    private $templatePosition = '1';


    /**
     * Set collectionPosition.
     *
     * @param int $collectionPosition
     *
     * @return Template
     */
    public function setCollectionPosition($collectionPosition)
    {
        $this->collectionPosition = $collectionPosition;

        return $this;
    }

    /**
     * Get collectionPosition.
     *
     * @return int
     */
    public function getCollectionPosition()
    {
        return $this->collectionPosition;
    }

    /**
     * Set templatePosition.
     *
     * @param int $templatePosition
     *
     * @return Template
     */
    public function setTemplatePosition($templatePosition)
    {
        $this->templatePosition = $templatePosition;

        return $this;
    }

    /**
     * Get templatePosition.
     *
     * @return int
     */
    public function getTemplatePosition()
    {
        return $this->templatePosition;
    }
    /**
     * @var int
     */
    private $recursiveIndexDepth = '1';


    /**
     * Set recursiveIndexDepth.
     *
     * @param int $recursiveIndexDepth
     *
     * @return Template
     */
    public function setRecursiveIndexDepth($recursiveIndexDepth)
    {
        $this->recursiveIndexDepth = $recursiveIndexDepth;

        return $this;
    }

    /**
     * Get recursiveIndexDepth.
     *
     * @return int
     */
    public function getRecursiveIndexDepth()
    {
        return $this->recursiveIndexDepth;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $csv_imports;


    /**
     * Add csvImport.
     *
     * @param \Entity\CSVBatch $csvImport
     *
     * @return Template
     */
    public function addCsvImport(\Entity\CSVBatch $csvImport)
    {
        $this->csv_imports[] = $csvImport;

        return $this;
    }

    /**
     * Remove csvImport.
     *
     * @param \Entity\CSVBatch $csvImport
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCsvImport(\Entity\CSVBatch $csvImport)
    {
        return $this->csv_imports->removeElement($csvImport);
    }

    /**
     * Get csvImports.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCsvImports()
    {
        return $this->csv_imports;
    }
}
