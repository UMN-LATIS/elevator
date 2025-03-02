<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AssetCache
 *
 * @ORM\Table(name="asset_cache", indexes={@ORM\Index(name="0", columns={"asset_id"}), @ORM\Index(name="1", columns={"needsRebuild"}), @ORM\Index(name="2", columns={"templateId"}), @ORM\Index(name="3", columns={"needsRebuild", "rebuildTimestamp"}), @ORM\Index(name="4", columns={"needsRebuild", "templateId"}), @ORM\Index(name="5", columns={"rebuildTimestamp"})})
 * @ORM\Entity
 */
class AssetCache
{
    /**
     * @var array|null
     *
     * @ORM\Column(name="relatedAssetCache", type="json_array", nullable=true)
     */
    private $relatedAssetCache;

    /**
     * @var array|null
     *
     * @ORM\Column(name="searchResultCache", type="json_array", nullable=true)
     */
    private $searchResultCache;

    /**
     * @var string|null
     *
     * @ORM\Column(name="primaryHandlerCache", type="string", nullable=true)
     */
    private $primaryHandlerCache;

    /**
     * @var int|null
     *
     * @ORM\Column(name="templateId", type="integer", nullable=true)
     */
    private $templateId;

    /**
     * @var bool
     *
     * @ORM\Column(name="needsRebuild", type="boolean", nullable=false)
     */
    private $needsRebuild;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="rebuildTimestamp", type="datetime", nullable=true)
     */
    private $rebuildTimestamp;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="asset_cache_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Entity\Asset
     *
     * @ORM\OneToOne(targetEntity="Entity\Asset", inversedBy="assetCache")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="asset_id", referencedColumnName="id", unique=true)
     * })
     */
    private $asset;


}
