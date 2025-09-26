<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Instance
 */
#[ORM\Table(name: 'instances')]
#[ORM\Entity]
class Instance
{
    /** 
     * Elevator
     * @var array|null
     */
    public $queryHandoff = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string')]
    private $name;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'domain', type: 'string', nullable: true)]
    private $domain;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'ownerHomepage', type: 'string', nullable: true)]
    private $ownerHomepage;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'amazonS3Key', type: 'string', nullable: true)]
    private $amazonS3Key;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 's3StorageType', type: 'string', nullable: true)]
    private $s3StorageType;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'defaultBucket', type: 'string', nullable: true)]
    private $defaultBucket;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'bucketRegion', type: 'string', nullable: true)]
    private $bucketRegion;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'amazonS3Secret', type: 'string', nullable: true)]
    private $amazonS3Secret;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'googleAnalyticsKey', type: 'string', nullable: true)]
    private $googleAnalyticsKey;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'introText', type: 'text', nullable: true)]
    private $introText;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'createdAt', type: 'datetime', nullable: true)]
    private $createdAt;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'modifiedAt', type: 'datetime', nullable: true)]
    private $modifiedAt;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'useCustomHeader', type: 'integer', nullable: true)]
    private $useCustomHeader;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'useCustomCSS', type: 'boolean', nullable: true)]
    private $useCustomCSS;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'useHeaderLogo', type: 'boolean', nullable: true)]
    private $useHeaderLogo;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'useCentralAuth', type: 'boolean', nullable: true)]
    private $useCentralAuth;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'hideVideoAudio', type: 'boolean', nullable: true)]
    private $hideVideoAudio;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'featuredAsset', type: 'string', nullable: true)]
    private $featuredAsset;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'featuredAssetText', type: 'text', nullable: true)]
    private $featuredAssetText;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'customHeaderText', type: 'text', nullable: true)]
    private $customHeaderText;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'customFooterText', type: 'text', nullable: true)]
    private $customFooterText;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'customHeaderCSS', type: 'text', nullable: true)]
    private $customHeaderCSS;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'customHeaderImage', type: 'blob', nullable: true)]
    private $customHeaderImage;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'allowIndexing', type: 'boolean', nullable: true)]
    private $allowIndexing;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'showCollectionInSearchResults', type: 'boolean', nullable: true)]
    private $showCollectionInSearchResults;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'showTemplateInSearchResults', type: 'boolean', nullable: true, options: ["default"=> false])]
    private $showTemplateInSearchResults = '0';

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'showPreviousNextSearchResults', type: 'boolean', nullable: true)]
    private $showPreviousNextSearchResults;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'useVoyagerViewer', type: 'boolean', nullable: true)]
    private $useVoyagerViewer;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'automaticAltText', type: 'boolean', nullable: true)]
    private $automaticAltText;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'enableHLSStreaming', type: 'boolean', nullable: true)]
    private $enableHLSStreaming;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'enableInterstitial', type: 'boolean', nullable: true)]
    private $enableInterstitial;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'interstitialText', type: 'text', nullable: true)]
    private $interstitialText;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'notes', type: 'text', nullable: true)]
    private $notes;

    /**
     * @var int
     */
    #[ORM\Column(name: 'interfaceVersion', type: 'integer', nullable: false,options: ["default"=> 0])]
    private $interfaceVersion = '0';

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'defaultTheme', type: 'text', nullable: true)]
    private $defaultTheme;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'enableThemes', type: 'boolean', nullable: true, options: ["default"=> false])]
    private $enableThemes = '0';

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'customHomeRedirect', type: 'string', nullable: true)]
    private $customHomeRedirect;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'maximumMoreLikeThis', type: 'integer', nullable: true, options: ['default' => '3'])]
    private $maximumMoreLikeThis = 3;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'defaultTextTruncationHeight', type: 'integer', nullable: true, options: ['default' => '72'])]
    private $defaultTextTruncationHeight = 72;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'availableThemes', type: 'json', nullable: true, options: ['jsonb' => true])]
    private $availableThemes;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\InstancePermission::class, mappedBy: 'instance', cascade: ['remove'])]
    private $permissions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\RecentCollection::class, mappedBy: 'instance', cascade: ['remove'])]
    private $recentcollections;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\InstanceGroup::class, mappedBy: 'instance', cascade: ['remove'])]
    private $groups;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\InstanceHandlerPermissions::class, mappedBy: 'instance', cascade: ['persist', 'remove'])]
    private $handler_permissions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\InstancePage::class, mappedBy: 'instance', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private $pages;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\Log::class, mappedBy: 'instance')]
    private $logs;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\JoinTable(name: 'instance_templates')]
    #[ORM\JoinColumn(name: 'instance_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'template_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: \Entity\Template::class, inversedBy: 'instances')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private $templates = array();

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\JoinTable(name: 'instance_collection')]
    #[ORM\JoinColumn(name: 'instance_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'collection_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: \Entity\Collection::class, inversedBy: 'instances')]
    #[ORM\OrderBy(['title' => 'ASC'])]
    private $collections = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->recentcollections = new \Doctrine\Common\Collections\ArrayCollection();
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->handler_permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->pages = new \Doctrine\Common\Collections\ArrayCollection();
        $this->logs = new \Doctrine\Common\Collections\ArrayCollection();
        $this->templates = new \Doctrine\Common\Collections\ArrayCollection();
        $this->collections = new \Doctrine\Common\Collections\ArrayCollection();
    }


    public function getCollectionsWithoutParent() {


        // TODO: Doctrine 2.5 has a much better way to do this using criteria
        //

        $result = $this->collections->filter(function($collection) {
          return (!$collection->getParent() || !$this->collections->contains($collection->getParent()));
        });

        return array_values($result->toArray());

//   $criteria = Criteria::create()->where(Criteria::expr()->in("id", $ids);

    // return $this->getComments()->matching($criteria);

    }


    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Instance
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
     * Set domain.
     *
     * @param string|null $domain
     *
     * @return Instance
     */
    public function setDomain($domain = null)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain.
     *
     * @return string|null
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set ownerHomepage.
     *
     * @param string|null $ownerHomepage
     *
     * @return Instance
     */
    public function setOwnerHomepage($ownerHomepage = null)
    {
        $this->ownerHomepage = $ownerHomepage;

        return $this;
    }

    /**
     * Get ownerHomepage.
     *
     * @return string|null
     */
    public function getOwnerHomepage()
    {
        return $this->ownerHomepage;
    }

    /**
     * Set amazonS3Key.
     *
     * @param string|null $amazonS3Key
     *
     * @return Instance
     */
    public function setAmazonS3Key($amazonS3Key = null)
    {
        $this->amazonS3Key = $amazonS3Key;

        return $this;
    }

    /**
     * Get amazonS3Key.
     *
     * @return string|null
     */
    public function getAmazonS3Key()
    {
        return $this->amazonS3Key;
    }

    /**
     * Set s3StorageType.
     *
     * @param string|null $s3StorageType
     *
     * @return Instance
     */
    public function setS3StorageType($s3StorageType = null)
    {
        $this->s3StorageType = $s3StorageType;

        return $this;
    }

    /**
     * Get s3StorageType.
     *
     * @return string|null
     */
    public function getS3StorageType()
    {
        return $this->s3StorageType;
    }

    /**
     * Set defaultBucket.
     *
     * @param string|null $defaultBucket
     *
     * @return Instance
     */
    public function setDefaultBucket($defaultBucket = null)
    {
        $this->defaultBucket = $defaultBucket;

        return $this;
    }

    /**
     * Get defaultBucket.
     *
     * @return string|null
     */
    public function getDefaultBucket()
    {
        return $this->defaultBucket;
    }

    /**
     * Set bucketRegion.
     *
     * @param string|null $bucketRegion
     *
     * @return Instance
     */
    public function setBucketRegion($bucketRegion = null)
    {
        $this->bucketRegion = $bucketRegion;

        return $this;
    }

    /**
     * Get bucketRegion.
     *
     * @return string|null
     */
    public function getBucketRegion()
    {
        return $this->bucketRegion;
    }

    /**
     * Set amazonS3Secret.
     *
     * @param string|null $amazonS3Secret
     *
     * @return Instance
     */
    public function setAmazonS3Secret($amazonS3Secret = null)
    {
        $this->amazonS3Secret = $amazonS3Secret;

        return $this;
    }

    /**
     * Get amazonS3Secret.
     *
     * @return string|null
     */
    public function getAmazonS3Secret()
    {
        return $this->amazonS3Secret;
    }

    /**
     * Set googleAnalyticsKey.
     *
     * @param string|null $googleAnalyticsKey
     *
     * @return Instance
     */
    public function setGoogleAnalyticsKey($googleAnalyticsKey = null)
    {
        $this->googleAnalyticsKey = $googleAnalyticsKey;

        return $this;
    }

    /**
     * Get googleAnalyticsKey.
     *
     * @return string|null
     */
    public function getGoogleAnalyticsKey()
    {
        return $this->googleAnalyticsKey;
    }

    /**
     * Set introText.
     *
     * @param string|null $introText
     *
     * @return Instance
     */
    public function setIntroText($introText = null)
    {
        $this->introText = $introText;

        return $this;
    }

    /**
     * Get introText.
     *
     * @return string|null
     */
    public function getIntroText()
    {
        return $this->introText;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime|null $createdAt
     *
     * @return Instance
     */
    public function setCreatedAt($createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set modifiedAt.
     *
     * @param \DateTime|null $modifiedAt
     *
     * @return Instance
     */
    public function setModifiedAt($modifiedAt = null)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * Get modifiedAt.
     *
     * @return \DateTime|null
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * Set useCustomHeader.
     *
     * @param int|null $useCustomHeader
     *
     * @return Instance
     */
    public function setUseCustomHeader($useCustomHeader = null)
    {
        $this->useCustomHeader = $useCustomHeader;

        return $this;
    }

    /**
     * Get useCustomHeader.
     *
     * @return int|null
     */
    public function getUseCustomHeader()
    {
        return $this->useCustomHeader;
    }

    /**
     * Set useCustomCSS.
     *
     * @param bool|null $useCustomCSS
     *
     * @return Instance
     */
    public function setUseCustomCSS($useCustomCSS = null)
    {
        $this->useCustomCSS = $useCustomCSS;

        return $this;
    }

    /**
     * Get useCustomCSS.
     *
     * @return bool|null
     */
    public function getUseCustomCSS()
    {
        return $this->useCustomCSS;
    }

    /**
     * Set useHeaderLogo.
     *
     * @param bool|null $useHeaderLogo
     *
     * @return Instance
     */
    public function setUseHeaderLogo($useHeaderLogo = null)
    {
        $this->useHeaderLogo = $useHeaderLogo;

        return $this;
    }

    /**
     * Get useHeaderLogo.
     *
     * @return bool|null
     */
    public function getUseHeaderLogo()
    {
        return $this->useHeaderLogo;
    }

    /**
     * Set useCentralAuth.
     *
     * @param bool|null $useCentralAuth
     *
     * @return Instance
     */
    public function setUseCentralAuth($useCentralAuth = null)
    {
        $this->useCentralAuth = $useCentralAuth;

        return $this;
    }

    /**
     * Get useCentralAuth.
     *
     * @return bool|null
     */
    public function getUseCentralAuth()
    {
        return $this->useCentralAuth;
    }

    /**
     * Set hideVideoAudio.
     *
     * @param bool|null $hideVideoAudio
     *
     * @return Instance
     */
    public function setHideVideoAudio($hideVideoAudio = null)
    {
        $this->hideVideoAudio = $hideVideoAudio;

        return $this;
    }

    /**
     * Get hideVideoAudio.
     *
     * @return bool|null
     */
    public function getHideVideoAudio()
    {
        return $this->hideVideoAudio;
    }

    /**
     * Set featuredAsset.
     *
     * @param string|null $featuredAsset
     *
     * @return Instance
     */
    public function setFeaturedAsset($featuredAsset = null)
    {
        $this->featuredAsset = $featuredAsset;

        return $this;
    }

    /**
     * Get featuredAsset.
     *
     * @return string|null
     */
    public function getFeaturedAsset()
    {
        return $this->featuredAsset;
    }

    /**
     * Set featuredAssetText.
     *
     * @param string|null $featuredAssetText
     *
     * @return Instance
     */
    public function setFeaturedAssetText($featuredAssetText = null)
    {
        $this->featuredAssetText = $featuredAssetText;

        return $this;
    }

    /**
     * Get featuredAssetText.
     *
     * @return string|null
     */
    public function getFeaturedAssetText()
    {
        return $this->featuredAssetText;
    }

    /**
     * Set customHeaderText.
     *
     * @param string|null $customHeaderText
     *
     * @return Instance
     */
    public function setCustomHeaderText($customHeaderText = null)
    {
        $this->customHeaderText = $customHeaderText;

        return $this;
    }

    /**
     * Get customHeaderText.
     *
     * @return string|null
     */
    public function getCustomHeaderText()
    {
        return $this->customHeaderText;
    }

    /**
     * Set customFooterText.
     *
     * @param string|null $customFooterText
     *
     * @return Instance
     */
    public function setCustomFooterText($customFooterText = null)
    {
        $this->customFooterText = $customFooterText;

        return $this;
    }

    /**
     * Get customFooterText.
     *
     * @return string|null
     */
    public function getCustomFooterText()
    {
        return $this->customFooterText;
    }

    /**
     * Set customHeaderCSS.
     *
     * @param string|null $customHeaderCSS
     *
     * @return Instance
     */
    public function setCustomHeaderCSS($customHeaderCSS = null)
    {
        $this->customHeaderCSS = $customHeaderCSS;

        return $this;
    }

    /**
     * Get customHeaderCSS.
     *
     * @return string|null
     */
    public function getCustomHeaderCSS()
    {
        return $this->customHeaderCSS;
    }

    /**
     * Set customHeaderImage.
     *
     * @param string|null $customHeaderImage
     *
     * @return Instance
     */
    public function setCustomHeaderImage($customHeaderImage = null)
    {
        $this->customHeaderImage = $customHeaderImage;

        return $this;
    }

    /**
     * Get customHeaderImage.
     *
     * @return string|null
     */
    public function getCustomHeaderImage()
    {
        return $this->customHeaderImage;
    }

    /**
     * Set allowIndexing.
     *
     * @param bool|null $allowIndexing
     *
     * @return Instance
     */
    public function setAllowIndexing($allowIndexing = null)
    {
        $this->allowIndexing = $allowIndexing;

        return $this;
    }

    /**
     * Get allowIndexing.
     *
     * @return bool|null
     */
    public function getAllowIndexing()
    {
        return $this->allowIndexing;
    }

    /**
     * Set showCollectionInSearchResults.
     *
     * @param bool|null $showCollectionInSearchResults
     *
     * @return Instance
     */
    public function setShowCollectionInSearchResults($showCollectionInSearchResults = null)
    {
        $this->showCollectionInSearchResults = $showCollectionInSearchResults;

        return $this;
    }

    /**
     * Get showCollectionInSearchResults.
     *
     * @return bool|null
     */
    public function getShowCollectionInSearchResults()
    {
        return $this->showCollectionInSearchResults;
    }

    /**
     * Set showTemplateInSearchResults.
     *
     * @param bool|null $showTemplateInSearchResults
     *
     * @return Instance
     */
    public function setShowTemplateInSearchResults($showTemplateInSearchResults = null)
    {
        $this->showTemplateInSearchResults = $showTemplateInSearchResults;

        return $this;
    }

    /**
     * Get showTemplateInSearchResults.
     *
     * @return bool|null
     */
    public function getShowTemplateInSearchResults()
    {
        return $this->showTemplateInSearchResults;
    }

    /**
     * Set showPreviousNextSearchResults.
     *
     * @param bool|null $showPreviousNextSearchResults
     *
     * @return Instance
     */
    public function setShowPreviousNextSearchResults($showPreviousNextSearchResults = null)
    {
        $this->showPreviousNextSearchResults = $showPreviousNextSearchResults;

        return $this;
    }

    /**
     * Get showPreviousNextSearchResults.
     *
     * @return bool|null
     */
    public function getShowPreviousNextSearchResults()
    {
        return $this->showPreviousNextSearchResults;
    }

    /**
     * Set useVoyagerViewer.
     *
     * @param bool|null $useVoyagerViewer
     *
     * @return Instance
     */
    public function setUseVoyagerViewer($useVoyagerViewer = null)
    {
        $this->useVoyagerViewer = $useVoyagerViewer;

        return $this;
    }

    /**
     * Get useVoyagerViewer.
     *
     * @return bool|null
     */
    public function getUseVoyagerViewer()
    {
        return $this->useVoyagerViewer;
    }

    /**
     * Set enableHLSStreaming.
     *
     * @param bool|null $enableHLSStreaming
     *
     * @return Instance
     */
    public function setEnableHLSStreaming($enableHLSStreaming = null)
    {
        $this->enableHLSStreaming = $enableHLSStreaming;

        return $this;
    }

    /**
     * Get enableHLSStreaming.
     *
     * @return bool|null
     */
    public function getEnableHLSStreaming()
    {
        return $this->enableHLSStreaming;
    }

    /**
     * Set enableInterstitial.
     *
     * @param bool|null $enableInterstitial
     *
     * @return Instance
     */
    public function setEnableInterstitial($enableInterstitial = null)
    {
        $this->enableInterstitial = $enableInterstitial;

        return $this;
    }

    /**
     * Get enableInterstitial.
     *
     * @return bool|null
     */
    public function getEnableInterstitial()
    {
        return $this->enableInterstitial;
    }

    /**
     * Set interstitialText.
     *
     * @param string|null $interstitialText
     *
     * @return Instance
     */
    public function setInterstitialText($interstitialText = null)
    {
        $this->interstitialText = $interstitialText;

        return $this;
    }

    /**
     * Get interstitialText.
     *
     * @return string|null
     */
    public function getInterstitialText()
    {
        return $this->interstitialText;
    }

    /**
     * Set notes.
     *
     * @param string|null $notes
     *
     * @return Instance
     */
    public function setNotes($notes = null)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes.
     *
     * @return string|null
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set interfaceVersion.
     *
     * @param int $interfaceVersion
     *
     * @return Instance
     */
    public function setInterfaceVersion($interfaceVersion)
    {
        $this->interfaceVersion = $interfaceVersion;

        return $this;
    }

    /**
     * Get interfaceVersion.
     *
     * @return int
     */
    public function getInterfaceVersion()
    {
        return $this->interfaceVersion;
    }

    /**
     * Set defaultTheme.
     *
     * @param string|null $defaultTheme
     *
     * @return Instance
     */
    public function setDefaultTheme($defaultTheme = null)
    {
        $this->defaultTheme = $defaultTheme;

        return $this;
    }

    /**
     * Get defaultTheme.
     *
     * @return string|null
     */
    public function getDefaultTheme()
    {
        return $this->defaultTheme;
    }

    /**
     * Set enableThemes.
     *
     * @param bool|null $enableThemes
     *
     * @return Instance
     */
    public function setEnableThemes($enableThemes = null)
    {
        $this->enableThemes = $enableThemes;

        return $this;
    }

    /**
     * Get enableThemes.
     *
     * @return bool|null
     */
    public function getEnableThemes()
    {
        return $this->enableThemes;
    }

    /**
     * Set customHomeRedirect.
     *
     * @param string|null $customHomeRedirect
     *
     * @return Instance
     */
    public function setCustomHomeRedirect($customHomeRedirect = null)
    {
        $this->customHomeRedirect = $customHomeRedirect;

        return $this;
    }

    /**
     * Get customHomeRedirect.
     *
     * @return string|null
     */
    public function getCustomHomeRedirect()
    {
        return $this->customHomeRedirect;
    }

    /**
     * Set maximumMoreLikeThis.
     *
     * @param int|null $maximumMoreLikeThis
     *
     * @return Instance
     */
    public function setMaximumMoreLikeThis($maximumMoreLikeThis = null)
    {
        $this->maximumMoreLikeThis = $maximumMoreLikeThis;

        return $this;
    }

    /**
     * Get maximumMoreLikeThis.
     *
     * @return int|null
     */
    public function getMaximumMoreLikeThis()
    {
        return $this->maximumMoreLikeThis;
    }

    /**
     * Set defaultTextTruncationHeight.
     *
     * @param int|null $defaultTextTruncationHeight
     *
     * @return Instance
     */
    public function setDefaultTextTruncationHeight($defaultTextTruncationHeight = null)
    {
        $this->defaultTextTruncationHeight = $defaultTextTruncationHeight;

        return $this;
    }

    /**
     * Get defaultTextTruncationHeight.
     *
     * @return int|null
     */
    public function getDefaultTextTruncationHeight()
    {
        return $this->defaultTextTruncationHeight;
    }

    /**
     * Set availableThemes.
     *
     * @param array|null $availableThemes
     *
     * @return Instance
     */
    public function setAvailableThemes($availableThemes = null)
    {
        $this->availableThemes = $availableThemes;

        return $this;
    }

    /**
     * Get availableThemes.
     *
     * @return array|null
     */
    public function getAvailableThemes()
    {
        return $this->availableThemes;
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
     * Add permission.
     *
     * @param \Entity\InstancePermission $permission
     *
     * @return Instance
     */
    public function addPermission(\Entity\InstancePermission $permission)
    {
        $this->permissions[] = $permission;

        return $this;
    }

    /**
     * Remove permission.
     *
     * @param \Entity\InstancePermission $permission
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePermission(\Entity\InstancePermission $permission)
    {
        return $this->permissions->removeElement($permission);
    }

    /**
     * Get permissions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Add recentcollection.
     *
     * @param \Entity\RecentCollection $recentcollection
     *
     * @return Instance
     */
    public function addRecentcollection(\Entity\RecentCollection $recentcollection)
    {
        $this->recentcollections[] = $recentcollection;

        return $this;
    }

    /**
     * Remove recentcollection.
     *
     * @param \Entity\RecentCollection $recentcollection
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeRecentcollection(\Entity\RecentCollection $recentcollection)
    {
        return $this->recentcollections->removeElement($recentcollection);
    }

    /**
     * Get recentcollections.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecentcollections()
    {
        return $this->recentcollections;
    }

    /**
     * Add group.
     *
     * @param \Entity\InstanceGroup $group
     *
     * @return Instance
     */
    public function addGroup(\Entity\InstanceGroup $group)
    {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * Remove group.
     *
     * @param \Entity\InstanceGroup $group
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeGroup(\Entity\InstanceGroup $group)
    {
        return $this->groups->removeElement($group);
    }

    /**
     * Get groups.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Add handlerPermission.
     *
     * @param \Entity\InstanceHandlerPermissions $handlerPermission
     *
     * @return Instance
     */
    public function addHandlerPermission(\Entity\InstanceHandlerPermissions $handlerPermission)
    {
        $this->handler_permissions[] = $handlerPermission;

        return $this;
    }

    /**
     * Remove handlerPermission.
     *
     * @param \Entity\InstanceHandlerPermissions $handlerPermission
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeHandlerPermission(\Entity\InstanceHandlerPermissions $handlerPermission)
    {
        return $this->handler_permissions->removeElement($handlerPermission);
    }

    /**
     * Get handlerPermissions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHandlerPermissions()
    {
        return $this->handler_permissions;
    }

    /**
     * Add page.
     *
     * @param \Entity\InstancePage $page
     *
     * @return Instance
     */
    public function addPage(\Entity\InstancePage $page)
    {
        $this->pages[] = $page;

        return $this;
    }

    /**
     * Remove page.
     *
     * @param \Entity\InstancePage $page
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePage(\Entity\InstancePage $page)
    {
        return $this->pages->removeElement($page);
    }

    /**
     * Get pages.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * Add log.
     *
     * @param \Entity\Log $log
     *
     * @return Instance
     */
    public function addLog(\Entity\Log $log)
    {
        $this->logs[] = $log;

        return $this;
    }

    /**
     * Remove log.
     *
     * @param \Entity\Log $log
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeLog(\Entity\Log $log)
    {
        return $this->logs->removeElement($log);
    }

    /**
     * Get logs.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * Add template.
     *
     * @param \Entity\Template $template
     *
     * @return Instance
     */
    public function addTemplate(\Entity\Template $template)
    {
        $this->templates[] = $template;

        return $this;
    }

    /**
     * Remove template.
     *
     * @param \Entity\Template $template
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeTemplate(\Entity\Template $template)
    {
        return $this->templates->removeElement($template);
    }

    /**
     * Get templates.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * Add collection.
     *
     * @param \Entity\Collection $collection
     *
     * @return Instance
     */
    public function addCollection(\Entity\Collection $collection)
    {
        $this->collections[] = $collection;

        return $this;
    }

    /**
     * Remove collection.
     *
     * @param \Entity\Collection $collection
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCollection(\Entity\Collection $collection)
    {
        return $this->collections->removeElement($collection);
    }

    /**
     * Get collections.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCollections()
    {
        return $this->collections;
    }

    /**
     * Set automaticAltText.
     *
     * @param bool|null $automaticAltText
     *
     * @return Instance
     */
    public function setAutomaticAltText($automaticAltText = null)
    {
        $this->automaticAltText = $automaticAltText;

        return $this;
    }

    /**
     * Get automaticAltText.
     *
     * @return bool|null
     */
    public function getAutomaticAltText()
    {
        return $this->automaticAltText;
    }
 
    /**
     * @var bool|null
     */
    private $autoloadMaxSearchResults = '0';


    /**
     * Set autoloadMaxSearchResults.
     *
     * @param bool|null $autoloadMaxSearchResults
     *
     * @return Instance
     */
    public function setAutoloadMaxSearchResults($autoloadMaxSearchResults = null)
    {
        $this->autoloadMaxSearchResults = $autoloadMaxSearchResults;

        return $this;
    }

    /**
     * Get autoloadMaxSearchResults.
     *
     * @return bool|null
     */
    public function getAutoloadMaxSearchResults()
    {
        return $this->autoloadMaxSearchResults;
    }
}
