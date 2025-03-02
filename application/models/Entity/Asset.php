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


    /**
     * Set assetId.
     *
     * @param string|null $assetId
     *
     * @return Asset
     */
    public function setAssetId($assetId = null)
    {
        $this->assetId = $assetId;

        return $this;
    }

    /**
     * Get assetId.
     *
     * @return string|null
     */
    public function getAssetId()
    {
        return $this->assetId;
    }

    /**
     * Set collectionId.
     *
     * @param int|null $collectionId
     *
     * @return Asset
     */
    public function setCollectionId($collectionId = null)
    {
        $this->collectionId = $collectionId;

        return $this;
    }

    /**
     * Get collectionId.
     *
     * @return int|null
     */
    public function getCollectionId()
    {
        return $this->collectionId;
    }

    /**
     * Set templateId.
     *
     * @param int|null $templateId
     *
     * @return Asset
     */
    public function setTemplateId($templateId = null)
    {
        $this->templateId = $templateId;

        return $this;
    }

    /**
     * Get templateId.
     *
     * @return int|null
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }

    /**
     * Set readyForDisplay.
     *
     * @param bool $readyForDisplay
     *
     * @return Asset
     */
    public function setReadyForDisplay($readyForDisplay)
    {
        $this->readyForDisplay = $readyForDisplay;

        return $this;
    }

    /**
     * Get readyForDisplay.
     *
     * @return bool
     */
    public function getReadyForDisplay()
    {
        return $this->readyForDisplay;
    }

    /**
     * Set modifiedBy.
     *
     * @param int $modifiedBy
     *
     * @return Asset
     */
    public function setModifiedBy($modifiedBy)
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    /**
     * Get modifiedBy.
     *
     * @return int
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }

    /**
     * Set createdBy.
     *
     * @param int $createdBy
     *
     * @return Asset
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy.
     *
     * @return int
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set deletedBy.
     *
     * @param int|null $deletedBy
     *
     * @return Asset
     */
    public function setDeletedBy($deletedBy = null)
    {
        $this->deletedBy = $deletedBy;

        return $this;
    }

    /**
     * Get deletedBy.
     *
     * @return int|null
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }

    /**
     * Set availableAfter.
     *
     * @param \DateTime|null $availableAfter
     *
     * @return Asset
     */
    public function setAvailableAfter($availableAfter = null)
    {
        $this->availableAfter = $availableAfter;

        return $this;
    }

    /**
     * Get availableAfter.
     *
     * @return \DateTime|null
     */
    public function getAvailableAfter()
    {
        return $this->availableAfter;
    }

    /**
     * Set modifiedAt.
     *
     * @param \DateTime $modifiedAt
     *
     * @return Asset
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
     * Set widgets.
     *
     * @param array|null $widgets
     *
     * @return Asset
     */
    public function setWidgets($widgets = null)
    {
        $this->widgets = $widgets;

        return $this;
    }

    /**
     * Get widgets.
     *
     * @return array|null
     */
    public function getWidgets()
    {
        return $this->widgets;
    }

    /**
     * Set collectionMigration.
     *
     * @param bool|null $collectionMigration
     *
     * @return Asset
     */
    public function setCollectionMigration($collectionMigration = null)
    {
        $this->collectionMigration = $collectionMigration;

        return $this;
    }

    /**
     * Get collectionMigration.
     *
     * @return bool|null
     */
    public function getCollectionMigration()
    {
        return $this->collectionMigration;
    }

    /**
     * Set deleted.
     *
     * @param bool|null $deleted
     *
     * @return Asset
     */
    public function setDeleted($deleted = null)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted.
     *
     * @return bool|null
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set deletedAt.
     *
     * @param \DateTime|null $deletedAt
     *
     * @return Asset
     */
    public function setDeletedAt($deletedAt = null)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt.
     *
     * @return \DateTime|null
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
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
     * Set assetCache.
     *
     * @param \Entity\AssetCache|null $assetCache
     *
     * @return Asset
     */
    public function setAssetCache(?\Entity\AssetCache $assetCache = null)
    {
        $this->assetCache = $assetCache;

        return $this;
    }

    /**
     * Get assetCache.
     *
     * @return \Entity\AssetCache|null
     */
    public function getAssetCache()
    {
        return $this->assetCache;
    }

    /**
     * Add revision.
     *
     * @param \Entity\Asset $revision
     *
     * @return Asset
     */
    public function addRevision(\Entity\Asset $revision)
    {
        $this->revisions[] = $revision;

        return $this;
    }

    /**
     * Remove revision.
     *
     * @param \Entity\Asset $revision
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeRevision(\Entity\Asset $revision)
    {
        return $this->revisions->removeElement($revision);
    }

    /**
     * Get revisions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRevisions()
    {
        return $this->revisions;
    }

    /**
     * Set revisionSource.
     *
     * @param \Entity\Asset|null $revisionSource
     *
     * @return Asset
     */
    public function setRevisionSource(?\Entity\Asset $revisionSource = null)
    {
        $this->revisionSource = $revisionSource;

        return $this;
    }

    /**
     * Get revisionSource.
     *
     * @return \Entity\Asset|null
     */
    public function getRevisionSource()
    {
        return $this->revisionSource;
    }

    /**
     * Set csvImport.
     *
     * @param \Entity\CSVBatch|null $csvImport
     *
     * @return Asset
     */
    public function setCsvImport(?\Entity\CSVBatch $csvImport = null)
    {
        $this->csvImport = $csvImport;

        return $this;
    }

    /**
     * Get csvImport.
     *
     * @return \Entity\CSVBatch|null
     */
    public function getCsvImport()
    {
        return $this->csvImport;
    }
}
