<?php

namespace Entity;

/**
 * User
 */
class User
{
    /**
     * @var string
     */
    private $emplid;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $userType;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $displayName;

    /**
     * @var boolean
     */
    private $fastUpload;

    /**
     * @var string
     */
    private $password;

    /**
     * @var boolean
     */
    private $isSuperAdmin;

    /**
     * @var boolean
     */
    private $hasExpiry;

    /**
     * @var \DateTime
     */
    private $expires;

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
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $recent_drawers;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $recent_searches;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $recent_collections;

    /**
     * @var \Entity\User
     */
    private $createdBy;

    /**
     * @var \Entity\Instance
     */
    private $instance;

    /**
     * @var \Entity\Instance
     */
    private $apiInstance;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->recent_drawers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->recent_searches = new \Doctrine\Common\Collections\ArrayCollection();
        $this->recent_collections = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set emplid
     *
     * @param string $emplid
     *
     * @return User
     */
    public function setEmplid($emplid)
    {
        $this->emplid = $emplid;

        return $this;
    }

    /**
     * Get emplid
     *
     * @return string
     */
    public function getEmplid()
    {
        return $this->emplid;
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set userType
     *
     * @param string $userType
     *
     * @return User
     */
    public function setUserType($userType)
    {
        $this->userType = $userType;

        return $this;
    }

    /**
     * Get userType
     *
     * @return string
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set displayName
     *
     * @param string $displayName
     *
     * @return User
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Get displayName
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Set fastUpload
     *
     * @param boolean $fastUpload
     *
     * @return User
     */
    public function setFastUpload($fastUpload)
    {
        $this->fastUpload = $fastUpload;

        return $this;
    }

    /**
     * Get fastUpload
     *
     * @return boolean
     */
    public function getFastUpload()
    {
        return $this->fastUpload;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set isSuperAdmin
     *
     * @param boolean $isSuperAdmin
     *
     * @return User
     */
    public function setIsSuperAdmin($isSuperAdmin)
    {
        $this->isSuperAdmin = $isSuperAdmin;

        return $this;
    }

    /**
     * Get isSuperAdmin
     *
     * @return boolean
     */
    public function getIsSuperAdmin()
    {
        return $this->isSuperAdmin;
    }

    /**
     * Set hasExpiry
     *
     * @param boolean $hasExpiry
     *
     * @return User
     */
    public function setHasExpiry($hasExpiry)
    {
        $this->hasExpiry = $hasExpiry;

        return $this;
    }

    /**
     * Get hasExpiry
     *
     * @return boolean
     */
    public function getHasExpiry()
    {
        return $this->hasExpiry;
    }

    /**
     * Set expires
     *
     * @param \DateTime $expires
     *
     * @return User
     */
    public function setExpires($expires)
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * Get expires
     *
     * @return \DateTime
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return User
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
     *
     * @return User
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add recentDrawer
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
     * Remove recentDrawer
     *
     * @param \Entity\RecentDrawer $recentDrawer
     */
    public function removeRecentDrawer(\Entity\RecentDrawer $recentDrawer)
    {
        $this->recent_drawers->removeElement($recentDrawer);
    }

    /**
     * Get recentDrawers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecentDrawers()
    {
        return $this->recent_drawers;
    }

    /**
     * Add recentSearch
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
     * Remove recentSearch
     *
     * @param \Entity\SearchEntry $recentSearch
     */
    public function removeRecentSearch(\Entity\SearchEntry $recentSearch)
    {
        $this->recent_searches->removeElement($recentSearch);
    }

    /**
     * Get recentSearches
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecentSearches()
    {
        return $this->recent_searches;
    }

    /**
     * Add recentCollection
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
     * Remove recentCollection
     *
     * @param \Entity\RecentCollection $recentCollection
     */
    public function removeRecentCollection(\Entity\RecentCollection $recentCollection)
    {
        $this->recent_collections->removeElement($recentCollection);
    }

    /**
     * Get recentCollections
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecentCollections()
    {
        return $this->recent_collections;
    }

    /**
     * Set createdBy
     *
     * @param \Entity\User $createdBy
     *
     * @return User
     */
    public function setCreatedBy(\Entity\User $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \Entity\User
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set instance
     *
     * @param \Entity\Instance $instance
     *
     * @return User
     */
    public function setInstance(\Entity\Instance $instance = null)
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * Get instance
     *
     * @return \Entity\Instance
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * Set apiInstance
     *
     * @param \Entity\Instance $apiInstance
     *
     * @return User
     */
    public function setApiInstance(\Entity\Instance $apiInstance = null)
    {
        $this->apiInstance = $apiInstance;

        return $this;
    }

    /**
     * Get apiInstance
     *
     * @return \Entity\Instance
     */
    public function getApiInstance()
    {
        return $this->apiInstance;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $api_keys;


    /**
     * Add apiKey
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
     * Remove apiKey
     *
     * @param \Entity\ApiKey $apiKey
     */
    public function removeApiKey(\Entity\ApiKey $apiKey)
    {
        $this->api_keys->removeElement($apiKey);
    }

    /**
     * Get apiKeys
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getApiKeys()
    {
        return $this->api_keys;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $csv_imports;


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
}
