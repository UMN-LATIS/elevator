<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Instance
 *
 * @ORM\Table(name="instances")
 * @ORM\Entity
 */
class Instance
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="domain", type="string", nullable=true)
     */
    private $domain;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ownerHomepage", type="string", nullable=true)
     */
    private $ownerHomepage;

    /**
     * @var string|null
     *
     * @ORM\Column(name="amazonS3Key", type="string", nullable=true)
     */
    private $amazonS3Key;

    /**
     * @var string|null
     *
     * @ORM\Column(name="s3StorageType", type="string", nullable=true)
     */
    private $s3StorageType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="defaultBucket", type="string", nullable=true)
     */
    private $defaultBucket;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bucketRegion", type="string", nullable=true)
     */
    private $bucketRegion;

    /**
     * @var string|null
     *
     * @ORM\Column(name="amazonS3Secret", type="string", nullable=true)
     */
    private $amazonS3Secret;

    /**
     * @var string|null
     *
     * @ORM\Column(name="googleAnalyticsKey", type="string", nullable=true)
     */
    private $googleAnalyticsKey;

    /**
     * @var string|null
     *
     * @ORM\Column(name="introText", type="text", nullable=true)
     */
    private $introText;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="modifiedAt", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * @var int|null
     *
     * @ORM\Column(name="useCustomHeader", type="integer", nullable=true)
     */
    private $useCustomHeader;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="useCustomCSS", type="boolean", nullable=true)
     */
    private $useCustomCSS;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="useHeaderLogo", type="boolean", nullable=true)
     */
    private $useHeaderLogo;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="useCentralAuth", type="boolean", nullable=true)
     */
    private $useCentralAuth;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="hideVideoAudio", type="boolean", nullable=true)
     */
    private $hideVideoAudio;

    /**
     * @var string|null
     *
     * @ORM\Column(name="featuredAsset", type="string", nullable=true)
     */
    private $featuredAsset;

    /**
     * @var string|null
     *
     * @ORM\Column(name="featuredAssetText", type="text", nullable=true)
     */
    private $featuredAssetText;

    /**
     * @var string|null
     *
     * @ORM\Column(name="customHeaderText", type="text", nullable=true)
     */
    private $customHeaderText;

    /**
     * @var string|null
     *
     * @ORM\Column(name="customFooterText", type="text", nullable=true)
     */
    private $customFooterText;

    /**
     * @var string|null
     *
     * @ORM\Column(name="customHeaderCSS", type="text", nullable=true)
     */
    private $customHeaderCSS;

    /**
     * @var string|null
     *
     * @ORM\Column(name="customHeaderImage", type="blob", nullable=true)
     */
    private $customHeaderImage;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="allowIndexing", type="boolean", nullable=true)
     */
    private $allowIndexing;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="showCollectionInSearchResults", type="boolean", nullable=true)
     */
    private $showCollectionInSearchResults;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="showTemplateInSearchResults", type="boolean", nullable=true)
     */
    private $showTemplateInSearchResults = '0';

    /**
     * @var bool|null
     *
     * @ORM\Column(name="showPreviousNextSearchResults", type="boolean", nullable=true)
     */
    private $showPreviousNextSearchResults;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="useVoyagerViewer", type="boolean", nullable=true)
     */
    private $useVoyagerViewer;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="enableHLSStreaming", type="boolean", nullable=true)
     */
    private $enableHLSStreaming;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="enableInterstitial", type="boolean", nullable=true)
     */
    private $enableInterstitial;

    /**
     * @var string|null
     *
     * @ORM\Column(name="interstitialText", type="text", nullable=true)
     */
    private $interstitialText;

    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    private $notes;

    /**
     * @var int
     *
     * @ORM\Column(name="interfaceVersion", type="integer", nullable=false)
     */
    private $interfaceVersion = '0';

    /**
     * @var string|null
     *
     * @ORM\Column(name="defaultTheme", type="text", nullable=true)
     */
    private $defaultTheme;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="enableThemes", type="boolean", nullable=true)
     */
    private $enableThemes = '0';

    /**
     * @var string|null
     *
     * @ORM\Column(name="customHomeRedirect", type="string", nullable=true)
     */
    private $customHomeRedirect;

    /**
     * @var int|null
     *
     * @ORM\Column(name="maximumMoreLikeThis", type="integer", nullable=true, options={"default"="3"})
     */
    private $maximumMoreLikeThis = 3;

    /**
     * @var int|null
     *
     * @ORM\Column(name="defaultTextTruncationHeight", type="integer", nullable=true, options={"default"="72"})
     */
    private $defaultTextTruncationHeight = 72;

    /**
     * @var array|null
     *
     * @ORM\Column(name="availableThemes", type="json_array", nullable=true)
     */
    private $availableThemes;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="instances_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\InstancePermission", mappedBy="instance", cascade={"remove"})
     */
    private $permissions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\RecentCollection", mappedBy="instance", cascade={"remove"})
     */
    private $recentcollections;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\InstanceGroup", mappedBy="instance", cascade={"remove"})
     */
    private $groups;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\InstanceHandlerPermissions", mappedBy="instance", cascade={"persist","remove"})
     */
    private $handler_permissions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\InstancePage", mappedBy="instance", cascade={"persist","remove"})
     * @ORM\OrderBy({
     *     "sortOrder"="ASC"
     * })
     */
    private $pages;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\Log", mappedBy="instance")
     */
    private $logs;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Entity\Template", inversedBy="instances")
     * @ORM\JoinTable(name="instance_templates",
     *   joinColumns={
     *     @ORM\JoinColumn(name="instance_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="template_id", referencedColumnName="id")
     *   }
     * )
     * @ORM\OrderBy({
     *     "name"="ASC"
     * })
     */
    private $templates = array();

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Entity\Collection", inversedBy="instances")
     * @ORM\JoinTable(name="instance_collection",
     *   joinColumns={
     *     @ORM\JoinColumn(name="instance_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="collection_id", referencedColumnName="id")
     *   }
     * )
     * @ORM\OrderBy({
     *     "title"="ASC"
     * })
     */
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

}
