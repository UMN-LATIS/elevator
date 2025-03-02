<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AssetCache
 */
#[ORM\Table(name: 'asset_cache')]
#[ORM\Index(name: 0, columns: ['asset_id'])]
#[ORM\Index(name: 1, columns: ['needsRebuild'])]
#[ORM\Index(name: 2, columns: ['templateId'])]
#[ORM\Index(name: 3, columns: ['needsRebuild', 'rebuildTimestamp'])]
#[ORM\Index(name: 4, columns: ['needsRebuild', 'templateId'])]
#[ORM\Index(name: 5, columns: ['rebuildTimestamp'])]
#[ORM\Entity]
class AssetCache
{
    /**
     * @var array|null
     */
    #[ORM\Column(name: 'relatedAssetCache', type: 'json', nullable: true)]
    private $relatedAssetCache;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'searchResultCache', type: 'json', nullable: true)]
    private $searchResultCache;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'primaryHandlerCache', type: 'string', nullable: true)]
    private $primaryHandlerCache;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'templateId', type: 'integer', nullable: true)]
    private $templateId;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'needsRebuild', type: 'boolean', nullable: false)]
    private $needsRebuild;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'rebuildTimestamp', type: 'datetime', nullable: true)]
    private $rebuildTimestamp;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'asset_cache_id_seq', allocationSize: 1, initialValue: 1)]
    private $id;

    /**
     * @var \Entity\Asset
     */
    #[ORM\JoinColumn(name: 'asset_id', referencedColumnName: 'id', unique: true)]
    #[ORM\OneToOne(targetEntity: \Entity\Asset::class, inversedBy: 'assetCache')]
    private $asset;



    /**
     * Set relatedAssetCache.
     *
     * @param array|null $relatedAssetCache
     *
     * @return AssetCache
     */
    public function setRelatedAssetCache($relatedAssetCache = null)
    {
        $this->relatedAssetCache = $relatedAssetCache;

        return $this;
    }

    /**
     * Get relatedAssetCache.
     *
     * @return array|null
     */
    public function getRelatedAssetCache()
    {
        return $this->relatedAssetCache;
    }

    /**
     * Set searchResultCache.
     *
     * @param array|null $searchResultCache
     *
     * @return AssetCache
     */
    public function setSearchResultCache($searchResultCache = null)
    {
        $this->searchResultCache = $searchResultCache;

        return $this;
    }

    /**
     * Get searchResultCache.
     *
     * @return array|null
     */
    public function getSearchResultCache()
    {
        return $this->searchResultCache;
    }

    /**
     * Set primaryHandlerCache.
     *
     * @param string|null $primaryHandlerCache
     *
     * @return AssetCache
     */
    public function setPrimaryHandlerCache($primaryHandlerCache = null)
    {
        $this->primaryHandlerCache = $primaryHandlerCache;

        return $this;
    }

    /**
     * Get primaryHandlerCache.
     *
     * @return string|null
     */
    public function getPrimaryHandlerCache()
    {
        return $this->primaryHandlerCache;
    }

    /**
     * Set templateId.
     *
     * @param int|null $templateId
     *
     * @return AssetCache
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
     * Set needsRebuild.
     *
     * @param bool $needsRebuild
     *
     * @return AssetCache
     */
    public function setNeedsRebuild($needsRebuild)
    {
        $this->needsRebuild = $needsRebuild;

        return $this;
    }

    /**
     * Get needsRebuild.
     *
     * @return bool
     */
    public function getNeedsRebuild()
    {
        return $this->needsRebuild;
    }

    /**
     * Set rebuildTimestamp.
     *
     * @param \DateTime|null $rebuildTimestamp
     *
     * @return AssetCache
     */
    public function setRebuildTimestamp($rebuildTimestamp = null)
    {
        $this->rebuildTimestamp = $rebuildTimestamp;

        return $this;
    }

    /**
     * Get rebuildTimestamp.
     *
     * @return \DateTime|null
     */
    public function getRebuildTimestamp()
    {
        return $this->rebuildTimestamp;
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
     * Set asset.
     *
     * @param \Entity\Asset|null $asset
     *
     * @return AssetCache
     */
    public function setAsset(?\Entity\Asset $asset = null)
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * Get asset.
     *
     * @return \Entity\Asset|null
     */
    public function getAsset()
    {
        return $this->asset;
    }
}
