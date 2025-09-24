<?php

namespace Entity;


use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;

/**
 * Instance
 */
class Instance
{
    // adding to deal with deprecation warning
    public $queryHandoff = null;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var string
     */
    private $ownerHomepage;

    /**
     * @var string
     */
    private $amazonS3Key;

    /**
     * @var string
     */
    private $defaultBucket;

    /**
     * @var string
     */
    private $bucketRegion;

    /**
     * @var string
     */
    private $amazonS3Secret;

    /**
     * @var string
     */
    private $encodingcomKey;

    /**
     * @var string
     */
    private $encodingcomUser;

    /**
     * @var string
     */
    private $googleAnalyticsKey;

    /**
     * @var string
     */
    private $introText;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $modifiedAt;

    /**
     * @var integer
     */
    private $useCustomHeader;

    /**
     * @var boolean
     */
    private $useCustomCSS;

    /**
     * @var boolean
     */
    private $useHeaderLogo;

    /**
     * @var string
     */
    private $featuredAsset;

    /**
     * @var string
     */
    private $featuredAssetText;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $permissions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $groups;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $templates;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $collections;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->templates = new \Doctrine\Common\Collections\ArrayCollection();
        $this->collections = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Instance
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
     * Set domain
     *
     * @param string $domain
     * @return Instance
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set ownerHomepage
     *
     * @param string $ownerHomepage
     * @return Instance
     */
    public function setOwnerHomepage($ownerHomepage)
    {
        $this->ownerHomepage = $ownerHomepage;

        return $this;
    }

    /**
     * Get ownerHomepage
     *
     * @return string
     */
    public function getOwnerHomepage()
    {
        return $this->ownerHomepage;
    }

    /**
     * Set amazonS3Key
     *
     * @param string $amazonS3Key
     * @return Instance
     */
    public function setAmazonS3Key($amazonS3Key)
    {
        $this->amazonS3Key = $amazonS3Key;

        return $this;
    }

    /**
     * Get amazonS3Key
     *
     * @return string
     */
    public function getAmazonS3Key()
    {
        return $this->amazonS3Key;
    }

    /**
     * Set defaultBucket
     *
     * @param string $defaultBucket
     * @return Instance
     */
    public function setDefaultBucket($defaultBucket)
    {
        $this->defaultBucket = $defaultBucket;

        return $this;
    }

    /**
     * Get defaultBucket
     *
     * @return string
     */
    public function getDefaultBucket()
    {
        return $this->defaultBucket;
    }

    /**
     * Set bucketRegion
     *
     * @param string $bucketRegion
     * @return Instance
     */
    public function setBucketRegion($bucketRegion)
    {
        $this->bucketRegion = $bucketRegion;

        return $this;
    }

    /**
     * Get bucketRegion
     *
     * @return string
     */
    public function getBucketRegion()
    {
        return $this->bucketRegion;
    }

    /**
     * Set amazonS3Secret
     *
     * @param string $amazonS3Secret
     * @return Instance
     */
    public function setAmazonS3Secret($amazonS3Secret)
    {
        $this->amazonS3Secret = $amazonS3Secret;

        return $this;
    }

    /**
     * Get amazonS3Secret
     *
     * @return string
     */
    public function getAmazonS3Secret()
    {
        return $this->amazonS3Secret;
    }

    /**
     * Set encodingcomKey
     *
     * @param string $encodingcomKey
     * @return Instance
     */
    public function setEncodingcomKey($encodingcomKey)
    {
        $this->encodingcomKey = $encodingcomKey;

        return $this;
    }

    /**
     * Get encodingcomKey
     *
     * @return string
     */
    public function getEncodingcomKey()
    {
        return $this->encodingcomKey;
    }

    /**
     * Set encodingcomUser
     *
     * @param string $encodingcomUser
     * @return Instance
     */
    public function setEncodingcomUser($encodingcomUser)
    {
        $this->encodingcomUser = $encodingcomUser;

        return $this;
    }

    /**
     * Get encodingcomUser
     *
     * @return string
     */
    public function getEncodingcomUser()
    {
        return $this->encodingcomUser;
    }

    /**
     * Set googleAnalyticsKey
     *
     * @param string $googleAnalyticsKey
     * @return Instance
     */
    public function setGoogleAnalyticsKey($googleAnalyticsKey)
    {
        $this->googleAnalyticsKey = $googleAnalyticsKey;

        return $this;
    }

    /**
     * Get googleAnalyticsKey
     *
     * @return string
     */
    public function getGoogleAnalyticsKey()
    {
        return $this->googleAnalyticsKey;
    }

    /**
     * Set introText
     *
     * @param string $introText
     * @return Instance
     */
    public function setIntroText($introText)
    {
        $this->introText = $introText;

        return $this;
    }

    /**
     * Get introText
     *
     * @return string
     */
    public function getIntroText()
    {
        return $this->introText;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Instance
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
     * @return Instance
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
     * Set useCustomHeader
     *
     * @param integer $useCustomHeader
     * @return Instance
     */
    public function setUseCustomHeader($useCustomHeader)
    {
        $this->useCustomHeader = $useCustomHeader;

        return $this;
    }

    /**
     * Get useCustomHeader
     *
     * @return integer
     */
    public function getUseCustomHeader()
    {
        return $this->useCustomHeader;
    }

    /**
     * Set useCustomCSS
     *
     * @param boolean $useCustomCSS
     * @return Instance
     */
    public function setUseCustomCSS($useCustomCSS)
    {
        $this->useCustomCSS = $useCustomCSS;

        return $this;
    }

    /**
     * Get useCustomCSS
     *
     * @return boolean
     */
    public function getUseCustomCSS()
    {
        return $this->useCustomCSS;
    }

    /**
     * Set useHeaderLogo
     *
     * @param boolean $useHeaderLogo
     * @return Instance
     */
    public function setUseHeaderLogo($useHeaderLogo)
    {
        $this->useHeaderLogo = $useHeaderLogo;

        return $this;
    }

    /**
     * Get useHeaderLogo
     *
     * @return boolean
     */
    public function getUseHeaderLogo()
    {
        return $this->useHeaderLogo;
    }

    /**
     * Set featuredAsset
     *
     * @param string $featuredAsset
     * @return Instance
     */
    public function setFeaturedAsset($featuredAsset)
    {
        $this->featuredAsset = $featuredAsset;

        return $this;
    }

    /**
     * Get featuredAsset
     *
     * @return string
     */
    public function getFeaturedAsset()
    {
        return $this->featuredAsset;
    }

    /**
     * Set featuredAssetText
     *
     * @param string $featuredAssetText
     * @return Instance
     */
    public function setFeaturedAssetText($featuredAssetText)
    {
        $this->featuredAssetText = $featuredAssetText;

        return $this;
    }

    /**
     * Get featuredAssetText
     *
     * @return string
     */
    public function getFeaturedAssetText()
    {
        return $this->featuredAssetText;
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
     * Add permissions
     *
     * @param \Entity\InstancePermission $permissions
     * @return Instance
     */
    public function addPermission(\Entity\InstancePermission $permissions)
    {
        $this->permissions[] = $permissions;

        return $this;
    }

    /**
     * Remove permissions
     *
     * @param \Entity\InstancePermission $permissions
     */
    public function removePermission(\Entity\InstancePermission $permissions)
    {
        $this->permissions->removeElement($permissions);
    }

    /**
     * Get permissions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Add groups
     *
     * @param \Entity\InstanceGroup $groups
     * @return Instance
     */
    public function addGroup(\Entity\InstanceGroup $groups)
    {
        $this->groups[] = $groups;

        return $this;
    }

    /**
     * Remove groups
     *
     * @param \Entity\InstanceGroup $groups
     */
    public function removeGroup(\Entity\InstanceGroup $groups)
    {
        $this->groups->removeElement($groups);
    }

    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Add templates
     *
     * @param \Entity\Template $templates
     * @return Instance
     */
    public function addTemplate(\Entity\Template $templates)
    {
        $this->templates[] = $templates;

        return $this;
    }

    /**
     * Remove templates
     *
     * @param \Entity\Template $templates
     */
    public function removeTemplate(\Entity\Template $templates)
    {
        $this->templates->removeElement($templates);
    }

    /**
     * Get templates
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * Add collections
     *
     * @param \Entity\Collection $collections
     * @return Instance
     */
    public function addCollection(\Entity\Collection $collections)
    {
        $this->collections[] = $collections;

        return $this;
    }

    /**
     * Remove collections
     *
     * @param \Entity\Collection $collections
     */
    public function removeCollection(\Entity\Collection $collections)
    {
        $this->collections->removeElement($collections);
    }

    /**
     * Get collections
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCollections()
    {
        return $this->collections;
    }

    public function getCollectionsWithoutParent() {


        // TODO: Doctrine 2.5 has a much better way to do this using criteria
        //

        $result = $this->collections->filter(function($collection) {
          return (!$collection->getParent() || !$this->collections->contains($collection->getParent()));
        });

        return array_values($result->toArray());

//   $criteria = Criteria::create()->where(Criteria::expr()->in("id", $ids);

    //return $this->getComments()->matching($criteria);

    }

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $handler_permissions;


    /**
     * Add handler_permissions
     *
     * @param \Entity\InstanceHandlerPermissions $handlerPermissions
     * @return Instance
     */
    public function addHandlerPermission(\Entity\InstanceHandlerPermissions $handlerPermissions)
    {
        $this->handler_permissions[] = $handlerPermissions;

        return $this;
    }

    /**
     * Remove handler_permissions
     *
     * @param \Entity\InstanceHandlerPermissions $handlerPermissions
     */
    public function removeHandlerPermission(\Entity\InstanceHandlerPermissions $handlerPermissions)
    {
        $this->handler_permissions->removeElement($handlerPermissions);
    }

    /**
     * Get handler_permissions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHandlerPermissions()
    {
        return $this->handler_permissions;
    }
    /**
     * @var boolean
     */
    private $useCentralAuth;


    /**
     * Set useCentralAuth
     *
     * @param boolean $useCentralAuth
     * @return Instance
     */
    public function setUseCentralAuth($useCentralAuth)
    {
        $this->useCentralAuth = $useCentralAuth;

        return $this;
    }

    /**
     * Get useCentralAuth
     *
     * @return boolean
     */
    public function getUseCentralAuth()
    {
        return $this->useCentralAuth;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $pages;


    /**
     * Add pages
     *
     * @param \Entity\InstancePage $pages
     * @return Instance
     */
    public function addPage(\Entity\InstancePage $pages)
    {
        $this->pages[] = $pages;

        return $this;
    }

    /**
     * Remove pages
     *
     * @param \Entity\InstancePage $pages
     */
    public function removePage(\Entity\InstancePage $pages)
    {
        $this->pages->removeElement($pages);
    }

    /**
     * Get pages
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPages()
    {
        return $this->pages;
    }
    /**
     * @var string
     */
    private $s3StorageType;


    /**
     * Set s3StorageType
     *
     * @param string $s3StorageType
     * @return Instance
     */
    public function setS3StorageType($s3StorageType)
    {
        $this->s3StorageType = $s3StorageType;

        return $this;
    }

    /**
     * Get s3StorageType
     *
     * @return string
     */
    public function getS3StorageType()
    {
        return $this->s3StorageType;
    }
    /**
     * @var string
     */
    private $clarifaiId;

    /**
     * @var string
     */
    private $clarifaiSecret;

    /**
     * @var string
     */
    private $boxKey;


    /**
     * Set clarifaiId
     *
     * @param string $clarifaiId
     *
     * @return Instance
     */
    public function setClarifaiId($clarifaiId)
    {
        $this->clarifaiId = $clarifaiId;

        return $this;
    }

    /**
     * Get clarifaiId
     *
     * @return string
     */
    public function getClarifaiId()
    {
        return $this->clarifaiId;
    }

    /**
     * Set clarifaiSecret
     *
     * @param string $clarifaiSecret
     *
     * @return Instance
     */
    public function setClarifaiSecret($clarifaiSecret)
    {
        $this->clarifaiSecret = $clarifaiSecret;

        return $this;
    }

    /**
     * Get clarifaiSecret
     *
     * @return string
     */
    public function getClarifaiSecret()
    {
        return $this->clarifaiSecret;
    }

    /**
     * Set boxKey
     *
     * @param string $boxKey
     *
     * @return Instance
     */
    public function setBoxKey($boxKey)
    {
        $this->boxKey = $boxKey;

        return $this;
    }

    /**
     * Get boxKey
     *
     * @return string
     */
    public function getBoxKey()
    {
        return $this->boxKey;
    }
    /**
     * @var boolean
     */
    private $hideVideoAudio;


    /**
     * Set hideVideoAudio
     *
     * @param boolean $hideVideoAudio
     *
     * @return Instance
     */
    public function setHideVideoAudio($hideVideoAudio)
    {
        $this->hideVideoAudio = $hideVideoAudio;

        return $this;
    }

    /**
     * Get hideVideoAudio
     *
     * @return boolean
     */
    public function getHideVideoAudio()
    {
        return $this->hideVideoAudio;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $logs;


    /**
     * Add log
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
     * Remove log
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
     * Get logs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLogs()
    {
        return $this->logs;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $users;


    /**
     * Add user
     *
     * @param \Entity\User $user
     *
     * @return Instance
     */
    public function addUser(\Entity\User $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user
     *
     * @param \Entity\User $user
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeUser(\Entity\User $user)
    {
        return $this->users->removeElement($user);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }
    /**
     * @var string
     */
    private $customHeaderText;

    /**
     * @var string
     */
    private $customHeaderCSS;

    /**
     * @var string
     */
    private $customHeaderImage;


    /**
     * Set customHeaderText
     *
     * @param string $customHeaderText
     *
     * @return Instance
     */
    public function setCustomHeaderText($customHeaderText)
    {
        $this->customHeaderText = $customHeaderText;

        return $this;
    }

    /**
     * Get customHeaderText
     *
     * @return string
     */
    public function getCustomHeaderText()
    {
        return $this->customHeaderText;
    }

    /**
     * Set customHeaderCSS
     *
     * @param string $customHeaderCSS
     *
     * @return Instance
     */
    public function setCustomHeaderCSS($customHeaderCSS)
    {
        $this->customHeaderCSS = $customHeaderCSS;

        return $this;
    }

    /**
     * Get customHeaderCSS
     *
     * @return string
     */
    public function getCustomHeaderCSS()
    {
        return $this->customHeaderCSS;
    }

    /**
     * Set customHeaderImage
     *
     * @param string $customHeaderImage
     *
     * @return Instance
     */
    public function setCustomHeaderImage($customHeaderImage)
    {
        $this->customHeaderImage = $customHeaderImage;

        return $this;
    }

    /**
     * Get customHeaderImage
     *
     * @return string
     */
    public function getCustomHeaderImage()
    {
        return $this->customHeaderImage;
    }
    /**
     * @var boolean
     */
    private $allowIndexing;


    /**
     * Set allowIndexing
     *
     * @param boolean $allowIndexing
     *
     * @return Instance
     */
    public function setAllowIndexing($allowIndexing)
    {
        $this->allowIndexing = $allowIndexing;

        return $this;
    }

    /**
     * Get allowIndexing
     *
     * @return boolean
     */
    public function getAllowIndexing()
    {
        return $this->allowIndexing;
    }
    /**
     * @var boolean
     */
    private $showCollectionInSearchResults;


    /**
     * Set showCollectionInSearchResults
     *
     * @param boolean $showCollectionInSearchResults
     *
     * @return Instance
     */
    public function setShowCollectionInSearchResults($showCollectionInSearchResults)
    {
        $this->showCollectionInSearchResults = $showCollectionInSearchResults;

        return $this;
    }

    /**
     * Get showCollectionInSearchResults
     *
     * @return boolean
     */
    public function getShowCollectionInSearchResults()
    {
        return $this->showCollectionInSearchResults;
    }
    /**
     * @var boolean
     */
    private $showPreviousNextSearchResults;


    /**
     * Set showPreviousNextSearchResults
     *
     * @param boolean $showPreviousNextSearchResults
     *
     * @return Instance
     */
    public function setShowPreviousNextSearchResults($showPreviousNextSearchResults)
    {
        $this->showPreviousNextSearchResults = $showPreviousNextSearchResults;

        return $this;
    }

    /**
     * Get showPreviousNextSearchResults
     *
     * @return boolean
     */
    public function getShowPreviousNextSearchResults()
    {
        return $this->showPreviousNextSearchResults;
    }
    /**
     * @var string
     */
    private $customFooterText;


    /**
     * Set customFooterText
     *
     * @param string $customFooterText
     *
     * @return Instance
     */
    public function setCustomFooterText($customFooterText)
    {
        $this->customFooterText = $customFooterText;

        return $this;
    }

    /**
     * Get customFooterText
     *
     * @return string
     */
    public function getCustomFooterText()
    {
        return $this->customFooterText;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $recentcollections;


    /**
     * Add recentcollection
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
     * Remove recentcollection
     *
     * @param \Entity\RecentCollection $recentcollection
     */
    public function removeRecentcollection(\Entity\RecentCollection $recentcollection)
    {
        $this->recentcollections->removeElement($recentcollection);
    }

    /**
     * Get recentcollections
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecentcollections()
    {
        return $this->recentcollections;
    }
    /**
     * @var boolean
     */
    private $enableHLSStreaming;

    /**
     * @var boolean
     */
    private $enableInterstitial;

    /**
     * @var string
     */
    private $interstitialText;


    /**
     * Set enableHLSStreaming
     *
     * @param boolean $enableHLSStreaming
     *
     * @return Instance
     */
    public function setEnableHLSStreaming($enableHLSStreaming)
    {
        $this->enableHLSStreaming = $enableHLSStreaming;

        return $this;
    }

    /**
     * Get enableHLSStreaming
     *
     * @return boolean
     */
    public function getEnableHLSStreaming()
    {
        return $this->enableHLSStreaming;
    }

    /**
     * Set enableInterstitial
     *
     * @param boolean $enableInterstitial
     *
     * @return Instance
     */
    public function setEnableInterstitial($enableInterstitial)
    {
        $this->enableInterstitial = $enableInterstitial;

        return $this;
    }

    /**
     * Get enableInterstitial
     *
     * @return boolean
     */
    public function getEnableInterstitial()
    {
        return $this->enableInterstitial;
    }

    /**
     * Set interstitialText
     *
     * @param string $interstitialText
     *
     * @return Instance
     */
    public function setInterstitialText($interstitialText)
    {
        $this->interstitialText = $interstitialText;

        return $this;
    }

    /**
     * Get interstitialText
     *
     * @return string
     */
    public function getInterstitialText()
    {
        return $this->interstitialText;
    }
    /**
     * @var bool|null
     */
    private $showTemplateInSearchResults = '0';


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
     * @var string|null
     */
    private $notes;


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
     * @var int
     */
    private $interfaceVersion = '0';


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
     * @var string|null
     */
    private $defaultTheme;

    /**
     * @var bool|null
     */
    private $enableThemes = '0';

    /**
     * @var int|null
     */
    private $maximumMoreLikeThis = '3';

    /**
     * height of collapsed text area widget in pixels
     * @var int|null
     */
    private $defaultTextTruncationHeight = 72;



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
    public function setDefaultTextTruncationHeight($defaultTextTruncationHeight = null) {
        $this->defaultTextTruncationHeight = $defaultTextTruncationHeight;

        return $this;
    }


    /**
     * Get the height of the collapsed text area widget in pixels.
     * 
     * @return int|null
     */
    public function getDefaultTextTruncationHeight() {
        return $this->defaultTextTruncationHeight;
    }

    /**
     * @var array|null
     */
    private $availableThemes;


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
     * @var string|null
     */
    private $customHomeRedirect;


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
     * @var bool|null
     */
    private $useVoyagerViewer;


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
     * @var bool|null
     */
    private $automaticAltText = '0';


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
