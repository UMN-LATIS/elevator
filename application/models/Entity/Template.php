<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Template
 *
 * @ORM\Table(name="templates")
 * @ORM\Entity
 */
class Template
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modifiedAt", type="datetime")
     */
    private $modifiedAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="includeInSearch", type="boolean", options={"default"="1"})
     */
    private $includeInSearch = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="indexForSearching", type="boolean", options={"default"="1"})
     */
    private $indexForSearching = true;

    /**
     * @var int
     *
     * @ORM\Column(name="templateColor", type="integer")
     */
    private $templateColor;

    /**
     * @var int
     *
     * @ORM\Column(name="recursiveIndexDepth", type="integer", options={"default"="1"})
     */
    private $recursiveIndexDepth = 1;

    /**
     * @var bool
     *
     * @ORM\Column(name="isHidden", type="boolean")
     */
    private $isHidden;

    /**
     * @var bool
     *
     * @ORM\Column(name="showCollection", type="boolean")
     */
    private $showCollection = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="showTemplate", type="boolean")
     */
    private $showTemplate = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="collectionPosition", type="integer")
     */
    private $collectionPosition = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="templatePosition", type="integer")
     */
    private $templatePosition = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="templates_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\Widget", mappedBy="template", cascade={"remove"})
     * @ORM\OrderBy({
     *     "id"="ASC"
     * })
     */
    private $widgets;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\CSVBatch", mappedBy="template")
     */
    private $csv_imports;

    /**
     * @var \Entity\Template
     *
     * @ORM\ManyToOne(targetEntity="Entity\Template")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="source_template_id", referencedColumnName="id")
     * })
     */
    private $source_template;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Entity\Instance", mappedBy="templates")
     */
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

}
