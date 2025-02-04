<?php

namespace Entity;

/**
 * Asset
 */
class Asset
{
    /**
     * @var string
     */
    private $assetId;

    /**
     * @var integer
     */
    private $collectionId;

    /**
     * @var integer
     */
    private $templateId;

    /**
     * @var boolean
     */
    private $readyForDisplay;

    /**
     * @var integer
     */
    private $modifiedBy;

    /**
     * @var \DateTime
     */
    private $availableAfter;

    /**
     * @var \DateTime
     */
    private $modifiedAt;

    /**
     * @var array
     */
    private $widgets;

    /**
     * @var integer
     */
    private $id;


    /**
     * Set assetId
     *
     * @param string $assetId
     *
     * @return Asset
     */
    public function setAssetId($assetId)
    {
        $this->assetId = $assetId;

        return $this;
    }

    /**
     * Get assetId
     *
     * @return string
     */
    public function getAssetId()
    {
        return $this->assetId;
    }

    /**
     * Set collectionId
     *
     * @param integer $collectionId
     *
     * @return Asset
     */
    public function setCollectionId($collectionId)
    {
        $this->collectionId = $collectionId;

        return $this;
    }

    /**
     * Get collectionId
     *
     * @return integer
     */
    public function getCollectionId()
    {
        return $this->collectionId;
    }

    /**
     * Set templateId
     *
     * @param integer $templateId
     *
     * @return Asset
     */
    public function setTemplateId($templateId)
    {
        $this->templateId = $templateId;

        return $this;
    }

    /**
     * Get templateId
     *
     * @return integer
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }

    /**
     * Set readyForDisplay
     *
     * @param boolean $readyForDisplay
     *
     * @return Asset
     */
    public function setReadyForDisplay($readyForDisplay)
    {
        $this->readyForDisplay = $readyForDisplay;

        return $this;
    }

    /**
     * Get readyForDisplay
     *
     * @return boolean
     */
    public function getReadyForDisplay()
    {
        return $this->readyForDisplay;
    }

    /**
     * Set modifiedBy
     *
     * @param integer $modifiedBy
     *
     * @return Asset
     */
    public function setModifiedBy($modifiedBy)
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    /**
     * Get modifiedBy
     *
     * @return integer
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }

    /**
     * Set availableAfter
     *
     * @param \DateTime $availableAfter
     *
     * @return Asset
     */
    public function setAvailableAfter($availableAfter)
    {
        $this->availableAfter = $availableAfter;

        return $this;
    }

    /**
     * Get availableAfter
     *
     * @return \DateTime
     */
    public function getAvailableAfter()
    {
        return $this->availableAfter;
    }

    /**
     * Set modifiedAt
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
     * Get modifiedAt
     *
     * @return \DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * Set widgets
     *
     * @param array $widgets
     *
     * @return Asset
     */
    public function setWidgets($widgets)
    {
        $this->widgets = $widgets;

        return $this;
    }

    /**
     * Get widgets
     *
     * @return array
     */
    public function getWidgets()
    {
        return $this->widgets;
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
     * @var \Doctrine\Common\Collections\Collection
     */
    private $revisions;

    /**
     * @var \Entity\Asset
     */
    private $revisionSource;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->revisions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add revision
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
     * Remove revision
     *
     * @param \Entity\Asset $revision
     */
    public function removeRevision(\Entity\Asset $revision)
    {
        $this->revisions->removeElement($revision);
    }

    /**
     * Get revisions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRevisions()
    {
        return $this->revisions;
    }

    /**
     * Set revisionSource
     *
     * @param \Entity\Asset $revisionSource
     *
     * @return Asset
     */
    public function setRevisionSource(? \Entity\Asset $revisionSource = null)
    {
        $this->revisionSource = $revisionSource;

        return $this;
    }

    /**
     * Get revisionSource
     *
     * @return \Entity\Asset
     */
    public function getRevisionSource()
    {
        return $this->revisionSource;
    }
    /**
     * @var integer
     */
    private $createdBy;


    /**
     * Set createdBy
     *
     * @param integer $createdBy
     *
     * @return Asset
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return integer
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }
    /**
     * @var boolean
     */
    private $collectionMigration;

    /**
     * @var integer
     */
    private $cachedUploadCount;

    /**
     * @var string
     */
    private $cachedPrimaryFileHandler;

    /**
     * @var boolean
     */
    private $deleted;

    /**
     * @var \DateTime
     */
    private $deletedAt;

    /**
     * @var array
     */
    private $cachedLocationData;

    /**
     * @var array
     */
    private $cachedDateData;


    /**
     * Set collectionMigration
     *
     * @param boolean $collectionMigration
     *
     * @return Asset
     */
    public function setCollectionMigration($collectionMigration)
    {
        $this->collectionMigration = $collectionMigration;

        return $this;
    }

    /**
     * Get collectionMigration
     *
     * @return boolean
     */
    public function getCollectionMigration()
    {
        return $this->collectionMigration;
    }

    /**
     * Set cachedUploadCount
     *
     * @param integer $cachedUploadCount
     *
     * @return Asset
     */
    public function setCachedUploadCount($cachedUploadCount)
    {
        $this->cachedUploadCount = $cachedUploadCount;

        return $this;
    }

    /**
     * Get cachedUploadCount
     *
     * @return integer
     */
    public function getCachedUploadCount()
    {
        return $this->cachedUploadCount;
    }

    /**
     * Set cachedPrimaryFileHandler
     *
     * @param string $cachedPrimaryFileHandler
     *
     * @return Asset
     */
    public function setCachedPrimaryFileHandler($cachedPrimaryFileHandler)
    {
        $this->cachedPrimaryFileHandler = $cachedPrimaryFileHandler;

        return $this;
    }

    /**
     * Get cachedPrimaryFileHandler
     *
     * @return string
     */
    public function getCachedPrimaryFileHandler()
    {
        return $this->cachedPrimaryFileHandler;
    }

    /**
     * Set deleted
     *
     * @param boolean $deleted
     *
     * @return Asset
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return boolean
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Asset
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Set cachedLocationData
     *
     * @param array $cachedLocationData
     *
     * @return Asset
     */
    public function setCachedLocationData($cachedLocationData)
    {
        $this->cachedLocationData = $cachedLocationData;

        return $this;
    }

    /**
     * Get cachedLocationData
     *
     * @return array
     */
    public function getCachedLocationData()
    {
        return $this->cachedLocationData;
    }

    /**
     * Set cachedDateData
     *
     * @param array $cachedDateData
     *
     * @return Asset
     */
    public function setCachedDateData($cachedDateData)
    {
        $this->cachedDateData = $cachedDateData;

        return $this;
    }

    /**
     * Get cachedDateData
     *
     * @return array
     */
    public function getCachedDateData()
    {
        return $this->cachedDateData;
    }
    /**
     * @var \Entity\AssetCache
     */
    private $assetCache;


    /**
     * Set assetCache
     *
     * @param \Entity\AssetCache $assetCache
     *
     * @return Asset
     */
    public function setAssetCache(? \Entity\AssetCache $assetCache = null)
    {
        $this->assetCache = $assetCache;

        return $this;
    }

    /**
     * Get assetCache
     *
     * @return \Entity\AssetCache
     */
    public function getAssetCache()
    {
        return $this->assetCache;
    }
    /**
     * @var int|null
     */
    private $deletedBy;


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
     * @var \Entity\CSVBatch
     */
    private $csvImport;


    /**
     * Set csvImport.
     *
     * @param \Entity\CSVBatch|null $csvImport
     *
     * @return Asset
     */
    public function setCsvImport(? \Entity\CSVBatch $csvImport = null)
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
