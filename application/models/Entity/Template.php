<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Template
 */
#[ORM\Table(name: 'templates')]
#[ORM\Entity]
class Template
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string')]
    private $name;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'createdAt', type: 'datetime')]
    private $createdAt;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'modifiedAt', type: 'datetime')]
    private $modifiedAt;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'includeInSearch', type: 'boolean', options: ['default' => '1'])]
    private $includeInSearch = true;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'indexForSearching', type: 'boolean', options: ['default' => '1'])]
    private $indexForSearching = true;

    /**
     * @var int
     */
    #[ORM\Column(name: 'templateColor', type: 'integer')]
    private $templateColor;

    /**
     * @var int
     */
    #[ORM\Column(name: 'recursiveIndexDepth', type: 'integer', options: ['default' => '1'])]
    private $recursiveIndexDepth = 1;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'isHidden', type: 'boolean')]
    private $isHidden;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'showCollection', type: 'boolean')]
    private $showCollection = '0';

    /**
     * @var bool
     */
    #[ORM\Column(name: 'showTemplate', type: 'boolean')]
    private $showTemplate = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'collectionPosition', type: 'integer')]
    private $collectionPosition = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'templatePosition', type: 'integer')]
    private $templatePosition = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'templates_id_seq', allocationSize: 1, initialValue: 1)]
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\Widget::class, mappedBy: 'template', cascade: ['remove'])]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private $widgets;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\CSVBatch::class, mappedBy: 'template')]
    private $csv_imports;

    /**
     * @var \Entity\Template
     */
    #[ORM\JoinColumn(name: 'source_template_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\Template::class)]
    private $source_template;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\ManyToMany(targetEntity: \Entity\Instance::class, mappedBy: 'templates')]
    private $instances = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->widgets = new \Doctrine\Common\Collections\ArrayCollection();
        $this->csv_imports = new \Doctrine\Common\Collections\ArrayCollection();
        $this->instances = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Template
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return Template
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set modifiedAt.
     *
     * @param \DateTime $modifiedAt
     *
     * @return Template
     */
    public function setModifiedAt($modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * Get modifiedAt.
     *
     * @return \DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * Set includeInSearch.
     *
     * @param bool $includeInSearch
     *
     * @return Template
     */
    public function setIncludeInSearch($includeInSearch)
    {
        $this->includeInSearch = $includeInSearch;

        return $this;
    }

    /**
     * Get includeInSearch.
     *
     * @return bool
     */
    public function getIncludeInSearch()
    {
        return $this->includeInSearch;
    }

    /**
     * Set indexForSearching.
     *
     * @param bool $indexForSearching
     *
     * @return Template
     */
    public function setIndexForSearching($indexForSearching)
    {
        $this->indexForSearching = $indexForSearching;

        return $this;
    }

    /**
     * Get indexForSearching.
     *
     * @return bool
     */
    public function getIndexForSearching()
    {
        return $this->indexForSearching;
    }

    /**
     * Set templateColor.
     *
     * @param int $templateColor
     *
     * @return Template
     */
    public function setTemplateColor($templateColor)
    {
        $this->templateColor = $templateColor;

        return $this;
    }

    /**
     * Get templateColor.
     *
     * @return int
     */
    public function getTemplateColor()
    {
        return $this->templateColor;
    }

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
     * Set isHidden.
     *
     * @param bool $isHidden
     *
     * @return Template
     */
    public function setIsHidden($isHidden)
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    /**
     * Get isHidden.
     *
     * @return bool
     */
    public function getIsHidden()
    {
        return $this->isHidden;
    }

    /**
     * Set showCollection.
     *
     * @param bool $showCollection
     *
     * @return Template
     */
    public function setShowCollection($showCollection)
    {
        $this->showCollection = $showCollection;

        return $this;
    }

    /**
     * Get showCollection.
     *
     * @return bool
     */
    public function getShowCollection()
    {
        return $this->showCollection;
    }

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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add widget.
     *
     * @param \Entity\Widget $widget
     *
     * @return Template
     */
    public function addWidget(\Entity\Widget $widget)
    {
        $this->widgets[] = $widget;

        return $this;
    }

    /**
     * Remove widget.
     *
     * @param \Entity\Widget $widget
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeWidget(\Entity\Widget $widget)
    {
        return $this->widgets->removeElement($widget);
    }

    /**
     * Get widgets.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getWidgets()
    {
        return $this->widgets;
    }

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

    /**
     * Set sourceTemplate.
     *
     * @param \Entity\Template|null $sourceTemplate
     *
     * @return Template
     */
    public function setSourceTemplate(?\Entity\Template $sourceTemplate = null)
    {
        $this->source_template = $sourceTemplate;

        return $this;
    }

    /**
     * Get sourceTemplate.
     *
     * @return \Entity\Template|null
     */
    public function getSourceTemplate()
    {
        return $this->source_template;
    }

    /**
     * Add instance.
     *
     * @param \Entity\Instance $instance
     *
     * @return Template
     */
    public function addInstance(\Entity\Instance $instance)
    {
        $this->instances[] = $instance;

        return $this;
    }

    /**
     * Remove instance.
     *
     * @param \Entity\Instance $instance
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeInstance(\Entity\Instance $instance)
    {
        return $this->instances->removeElement($instance);
    }

    /**
     * Get instances.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInstances()
    {
        return $this->instances;
    }
}
