<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Asset
 *
 * @ORM\Table(name="assets", indexes={@ORM\Index(name="0", columns={"collectionId"}), @ORM\Index(name="1", columns={"templateId"}), @ORM\Index(name="2", columns={"assetId"}), @ORM\Index(name="3", columns={"readyForDisplay"}), @ORM\Index(name="4", columns={"widgets"}), @ORM\Index(name="5", columns={"createdBy"}), @ORM\Index(name="6", columns={"modifiedBy"})})
 * @ORM\Entity
 */
class Asset
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="assetId", type="string", nullable=true)
     */
    private $assetId;

    /**
     * @var int|null
     *
     * @ORM\Column(name="collectionId", type="integer", nullable=true)
     */
    private $collectionId;

    /**
     * @var int|null
     *
     * @ORM\Column(name="templateId", type="integer", nullable=true)
     */
    private $templateId;

    /**
     * @var bool
     *
     * @ORM\Column(name="readyForDisplay", type="boolean", nullable=false)
     */
    private $readyForDisplay;

    /**
     * @var int
     *
     * @ORM\Column(name="modifiedBy", type="integer")
     */
    private $modifiedBy;

    /**
     * @var int
     *
     * @ORM\Column(name="createdBy", type="integer")
     */
    private $createdBy;

    /**
     * @var int|null
     *
     * @ORM\Column(name="deletedBy", type="integer", nullable=true)
     */
    private $deletedBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="availableAfter", type="datetime", nullable=true)
     */
    private $availableAfter;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modifiedAt", type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    private $modifiedAt = 'CURRENT_TIMESTAMP';

    /**
     * @var array|null
     *
     * @ORM\Column(name="widgets", type="json_array", nullable=true)
     */
    private $widgets;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="collectionMigration", type="boolean", nullable=true)
     */
    private $collectionMigration;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="deleted", type="boolean", nullable=true)
     */
    private $deleted;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="deletedAt", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="assets_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Entity\AssetCache
     *
     * @ORM\OneToOne(targetEntity="Entity\AssetCache", mappedBy="asset", cascade={"remove"}, fetch="EAGER")
     */
    private $assetCache;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\Asset", mappedBy="revisionSource")
     * @ORM\OrderBy({
     *     "modifiedAt"="ASC"
     * })
     */
    private $revisions;

    /**
     * @var \Entity\Asset
     *
     * @ORM\ManyToOne(targetEntity="Entity\Asset", inversedBy="revisions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="revisionSource_id", referencedColumnName="id")
     * })
     */
    private $revisionSource;

    /**
     * @var \Entity\CSVBatch
     *
     * @ORM\ManyToOne(targetEntity="Entity\CSVBatch", inversedBy="assets")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="csvImport_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $csvImport;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->revisions = new \Doctrine\Common\Collections\ArrayCollection();
    }

}
