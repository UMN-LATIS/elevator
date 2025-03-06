<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * User
 */
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 0, columns: ['username'])]
#[ORM\Index(name: 1, columns: ['emplid'])]
#[ORM\Entity]
class User
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'emplid', type: 'string', nullable: true)]
    private $emplid;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'username', type: 'string', nullable: true)]
    private $username;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'userType', type: 'string', nullable: true)]
    private $userType;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'email', type: 'string', nullable: true)]
    private $email;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'displayName', type: 'string', nullable: true)]
    private $displayName;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'fastUpload', type: 'boolean', nullable: true)]
    private $fastUpload;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'password', type: 'string', nullable: true)]
    private $password;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'isSuperAdmin', type: 'boolean', nullable: true)]
    private $isSuperAdmin;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'hasExpiry', type: 'boolean', nullable: true)]
    private $hasExpiry;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'expires', type: 'datetime', nullable: true)]
    private $expires;

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
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\RecentDrawer::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private $recent_drawers;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\SearchEntry::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $recent_searches;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\RecentCollection::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private $recent_collections;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\CSVBatch::class, mappedBy: 'createdBy')]
    private $csv_imports;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\ApiKey::class, mappedBy: 'owner', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $api_keys;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\LTI13InstanceAssociation::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $lti_courses;

    /**
     * @var \Entity\User
     */
    #[ORM\JoinColumn(name: 'createdBy_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\User::class)]
    private $createdBy;

    /**
     * @var \Entity\Instance
     */
    #[ORM\JoinColumn(name: 'instance_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \Entity\Instance::class)]
    private $instance;

    /**
     * @var \Entity\Instance
     */
    #[ORM\JoinColumn(name: 'apiInstance_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \Entity\Instance::class)]
    private $apiInstance;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->recent_drawers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->recent_searches = new \Doctrine\Common\Collections\ArrayCollection();
        $this->recent_collections = new \Doctrine\Common\Collections\ArrayCollection();
        $this->csv_imports = new \Doctrine\Common\Collections\ArrayCollection();
        $this->api_keys = new \Doctrine\Common\Collections\ArrayCollection();
        $this->lti_courses = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * Set emplid.
     *
     * @param string|null $emplid
     *
     * @return User
     */
    public function setEmplid($emplid = null)
    {
        $this->emplid = $emplid;

        return $this;
    }

    /**
     * Get emplid.
     *
     * @return string|null
     */
    public function getEmplid()
    {
        return $this->emplid;
    }

    /**
     * Set username.
     *
     * @param string|null $username
     *
     * @return User
     */
    public function setUsername($username = null)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username.
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set userType.
     *
     * @param string|null $userType
     *
     * @return User
     */
    public function setUserType($userType = null)
    {
        $this->userType = $userType;

        return $this;
    }

    /**
     * Get userType.
     *
     * @return string|null
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * Set email.
     *
     * @param string|null $email
     *
     * @return User
     */
    public function setEmail($email = null)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set displayName.
     *
     * @param string|null $displayName
     *
     * @return User
     */
    public function setDisplayName($displayName = null)
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Get displayName.
     *
     * @return string|null
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Set fastUpload.
     *
     * @param bool|null $fastUpload
     *
     * @return User
     */
    public function setFastUpload($fastUpload = null)
    {
        $this->fastUpload = $fastUpload;

        return $this;
    }

    /**
     * Get fastUpload.
     *
     * @return bool|null
     */
    public function getFastUpload()
    {
        return $this->fastUpload;
    }

    /**
     * Set password.
     *
     * @param string|null $password
     *
     * @return User
     */
    public function setPassword($password = null)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set isSuperAdmin.
     *
     * @param bool|null $isSuperAdmin
     *
     * @return User
     */
    public function setIsSuperAdmin($isSuperAdmin = null)
    {
        $this->isSuperAdmin = $isSuperAdmin;

        return $this;
    }

    /**
     * Get isSuperAdmin.
     *
     * @return bool|null
     */
    public function getIsSuperAdmin()
    {
        return $this->isSuperAdmin;
    }

    /**
     * Set hasExpiry.
     *
     * @param bool|null $hasExpiry
     *
     * @return User
     */
    public function setHasExpiry($hasExpiry = null)
    {
        $this->hasExpiry = $hasExpiry;

        return $this;
    }

    /**
     * Get hasExpiry.
     *
     * @return bool|null
     */
    public function getHasExpiry()
    {
        return $this->hasExpiry;
    }

    /**
     * Set expires.
     *
     * @param \DateTime|null $expires
     *
     * @return User
     */
    public function setExpires($expires = null)
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * Get expires.
     *
     * @return \DateTime|null
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime|null $createdAt
     *
     * @return User
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
     * @return User
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add recentDrawer.
     *
     * @param \Entity\RecentDrawer $recentDrawer
     *
     * @return User
     */
    public function addRecentDrawer(\Entity\RecentDrawer $recentDrawer)
    {
        $this->recent_drawers[] = $recentDrawer;

        return $this;
    }

    /**
     * Remove recentDrawer.
     *
     * @param \Entity\RecentDrawer $recentDrawer
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeRecentDrawer(\Entity\RecentDrawer $recentDrawer)
    {
        return $this->recent_drawers->removeElement($recentDrawer);
    }

    /**
     * Get recentDrawers.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecentDrawers()
    {
        return $this->recent_drawers;
    }

    /**
     * Add recentSearch.
     *
     * @param \Entity\SearchEntry $recentSearch
     *
     * @return User
     */
    public function addRecentSearch(\Entity\SearchEntry $recentSearch)
    {
        $this->recent_searches[] = $recentSearch;

        return $this;
    }

    /**
     * Remove recentSearch.
     *
     * @param \Entity\SearchEntry $recentSearch
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeRecentSearch(\Entity\SearchEntry $recentSearch)
    {
        return $this->recent_searches->removeElement($recentSearch);
    }

    /**
     * Get recentSearches.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecentSearches()
    {
        return $this->recent_searches;
    }

    /**
     * Add recentCollection.
     *
     * @param \Entity\RecentCollection $recentCollection
     *
     * @return User
     */
    public function addRecentCollection(\Entity\RecentCollection $recentCollection)
    {
        $this->recent_collections[] = $recentCollection;

        return $this;
    }

    /**
     * Remove recentCollection.
     *
     * @param \Entity\RecentCollection $recentCollection
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeRecentCollection(\Entity\RecentCollection $recentCollection)
    {
        return $this->recent_collections->removeElement($recentCollection);
    }

    /**
     * Get recentCollections.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecentCollections()
    {
        return $this->recent_collections;
    }

    /**
     * Add csvImport.
     *
     * @param \Entity\CSVBatch $csvImport
     *
     * @return User
     */
    public function addCsvImport(\Entity\CSVBatch $csvImport)
    {
        $this->csv_imports[] = $csvImport;

        return $this;
    }

    /**
     * Remove csvImport.
     *
     * @param \Entity\CSVBatch $csvImport
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCsvImport(\Entity\CSVBatch $csvImport)
    {
        return $this->csv_imports->removeElement($csvImport);
    }

    /**
     * Get csvImports.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCsvImports()
    {
        return $this->csv_imports;
    }

    /**
     * Add apiKey.
     *
     * @param \Entity\ApiKey $apiKey
     *
     * @return User
     */
    public function addApiKey(\Entity\ApiKey $apiKey)
    {
        $this->api_keys[] = $apiKey;

        return $this;
    }

    /**
     * Remove apiKey.
     *
     * @param \Entity\ApiKey $apiKey
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeApiKey(\Entity\ApiKey $apiKey)
    {
        return $this->api_keys->removeElement($apiKey);
    }

    /**
     * Get apiKeys.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getApiKeys()
    {
        return $this->api_keys;
    }

    /**
     * Add ltiCourse.
     *
     * @param \Entity\LTI13InstanceAssociation $ltiCourse
     *
     * @return User
     */
    public function addLtiCourse(\Entity\LTI13InstanceAssociation $ltiCourse)
    {
        $this->lti_courses[] = $ltiCourse;

        return $this;
    }

    /**
     * Remove ltiCourse.
     *
     * @param \Entity\LTI13InstanceAssociation $ltiCourse
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeLtiCourse(\Entity\LTI13InstanceAssociation $ltiCourse)
    {
        return $this->lti_courses->removeElement($ltiCourse);
    }

    /**
     * Get ltiCourses.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLtiCourses()
    {
        return $this->lti_courses;
    }

    /**
     * Set createdBy.
     *
     * @param \Entity\User|null $createdBy
     *
     * @return User
     */
    public function setCreatedBy(?\Entity\User $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy.
     *
     * @return \Entity\User|null
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set instance.
     *
     * @param \Entity\Instance|null $instance
     *
     * @return User
     */
    public function setInstance(?\Entity\Instance $instance = null)
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * Get instance.
     *
     * @return \Entity\Instance|null
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * Set apiInstance.
     *
     * @param \Entity\Instance|null $apiInstance
     *
     * @return User
     */
    public function setApiInstance(?\Entity\Instance $apiInstance = null)
    {
        $this->apiInstance = $apiInstance;

        return $this;
    }

    /**
     * Get apiInstance.
     *
     * @return \Entity\Instance|null
     */
    public function getApiInstance()
    {
        return $this->apiInstance;
    }
}
