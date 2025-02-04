<?php

namespace Entity;

/**
 * AssetCache
 */
class AssetCache
{
    /**
     * @var array
     */
    private $relatedAssetCache;

    /**
     * @var array
     */
    private $searchResultCache;

    /**
     * @var string
     */
    private $primaryHandlerCache;

    /**
     * @var integer
     */
    private $templateId;

    /**
     * @var boolean
     */
    private $needsRebuild;

    /**
     * @var \DateTime
     */
    private $rebuildTimestamp;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Entity\Asset
     */
    private $asset;


    /**
     * Set relatedAssetCache
     *
     * @param array $relatedAssetCache
     *
     * @return AssetCache
     */
    public function setRelatedAssetCache($relatedAssetCache)
    {
        $this->relatedAssetCache = $relatedAssetCache;

        return $this;
    }

    /**
     * Get relatedAssetCache
     *
     * @return array
     */
    public function getRelatedAssetCache()
    {
        return $this->relatedAssetCache;
    }

    /**
     * Set searchResultCache
     *
     * @param array $searchResultCache
     *
     * @return AssetCache
     */
    public function setSearchResultCache($searchResultCache)
    {
        $this->searchResultCache = $searchResultCache;

        return $this;
    }

    /**
     * Get searchResultCache
     *
     * @return array
     */
    public function getSearchResultCache()
    {
        return $this->searchResultCache;
    }

    /**
     * Set primaryHandlerCache
     *
     * @param string $primaryHandlerCache
     *
     * @return AssetCache
     */
    public function setPrimaryHandlerCache($primaryHandlerCache)
    {
        $this->primaryHandlerCache = $primaryHandlerCache;

        return $this;
    }

    /**
     * Get primaryHandlerCache
     *
     * @return string
     */
    public function getPrimaryHandlerCache()
    {
        return $this->primaryHandlerCache;
    }

    /**
     * Set templateId
     *
     * @param integer $templateId
     *
     * @return AssetCache
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
     * Set needsRebuild
     *
     * @param boolean $needsRebuild
     *
     * @return AssetCache
     */
    public function setNeedsRebuild($needsRebuild)
    {
        $this->needsRebuild = $needsRebuild;

        return $this;
    }

    /**
     * Get needsRebuild
     *
     * @return boolean
     */
    public function getNeedsRebuild()
    {
        return $this->needsRebuild;
    }

    /**
     * Set rebuildTimestamp
     *
     * @param \DateTime $rebuildTimestamp
     *
     * @return AssetCache
     */
    public function setRebuildTimestamp($rebuildTimestamp)
    {
        $this->rebuildTimestamp = $rebuildTimestamp;

        return $this;
    }

    /**
     * Get rebuildTimestamp
     *
     * @return \DateTime
     */
    public function getRebuildTimestamp()
    {
        return $this->rebuildTimestamp;
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
     * Set asset
     *
     * @param \Entity\Asset $asset
     *
     * @return AssetCache
     */
    public function setAsset(? \Entity\Asset $asset = null)
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * Get asset
     *
     * @return \Entity\Asset
     */
    public function getAsset()
    {
        return $this->asset;
    }
}
