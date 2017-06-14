<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Collection
 */
class Collection
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $bucket;

    /**
     * @var string
     */
    private $s3Key;

    /**
     * @var string
     */
    private $s3Secret;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $modifiedAt;

    /**
     * @var string
     */
    private $bucketRegion;

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
    private $recent_collection;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $instances;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->recent_collection = new \Doctrine\Common\Collections\ArrayCollection();
        $this->instances = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Collection
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set bucket
     *
     * @param string $bucket
     * @return Collection
     */
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;

        return $this;
    }

    /**
     * Get bucket
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Set s3Key
     *
     * @param string $s3Key
     * @return Collection
     */
    public function setS3Key($s3Key)
    {
        $this->s3Key = $s3Key;

        return $this;
    }

    /**
     * Get s3Key
     *
     * @return string
     */
    public function getS3Key()
    {
        return $this->s3Key;
    }

    /**
     * Set s3Secret
     *
     * @param string $s3Secret
     * @return Collection
     */
    public function setS3Secret($s3Secret)
    {
        $this->s3Secret = $s3Secret;

        return $this;
    }

    /**
     * Get s3Secret
     *
     * @return string
     */
    public function getS3Secret()
    {
        return $this->s3Secret;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Collection
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
     * @return Collection
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
     * Set bucketRegion
     *
     * @param string $bucketRegion
     * @return Collection
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
     * @param \Entity\CollectionPermission $permissions
     * @return Collection
     */
    public function addPermission(\Entity\CollectionPermission $permissions)
    {
        $this->permissions[] = $permissions;

        return $this;
    }

    /**
     * Remove permissions
     *
     * @param \Entity\CollectionPermission $permissions
     */
    public function removePermission(\Entity\CollectionPermission $permissions)
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
     * Add recent_collection
     *
     * @param \Entity\RecentCollection $recentCollection
     * @return Collection
     */
    public function addRecentCollection(\Entity\RecentCollection $recentCollection)
    {
        $this->recent_collection[] = $recentCollection;

        return $this;
    }

    /**
     * Remove recent_collection
     *
     * @param \Entity\RecentCollection $recentCollection
     */
    public function removeRecentCollection(\Entity\RecentCollection $recentCollection)
    {
        $this->recent_collection->removeElement($recentCollection);
    }

    /**
     * Get recent_collection
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecentCollection()
    {
        return $this->recent_collection;
    }

    /**
     * Add instances
     *
     * @param \Entity\Instance $instances
     * @return Collection
     */
    public function addInstance(\Entity\Instance $instances)
    {
        $instances->addCollection($this);
        $this->instances[] = $instances;

        return $this;
    }

    /**
     * Remove instances
     *
     * @param \Entity\Instance $instances
     */
    public function removeInstance(\Entity\Instance $instances)
    {
        $this->instances->removeElement($instances);
    }

    /**
     * Get instances
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInstances()
    {
        return $this->instances;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;

    /**
     * @var \Entity\Collection
     */
    private $parent;


    /**
     * Add children
     *
     * @param \Entity\Collection $children
     * @return Collection
     */
    public function addChild(\Entity\Collection $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Entity\Collection $children
     */
    public function removeChild(\Entity\Collection $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }


    /**
     * Has Children
     * @return boolean
     */
    public function hasChildren() {
        return !$this->children->isEmpty();
    }

    public function hasBrowseableChildren() {
        $filteredArray = array_filter($this->children->toArray(), function($n) { return $n->getShowInBrowse();});
        return count($filteredArray)>0?true:false;
    }

    public function getFlattenedChildren() {
        $outputArray = array();
        foreach($this->children as $child) {
            $outputArray[] = $child;
            if($child->hasChildren()) {
                $outputArray = array_merge($outputArray, $child->getFlattenedChildren());
            }
        }
        return $outputArray;
    }

    /**
     * Set parent
     *
     * @param \Entity\Collection $parent
     * @return Collection
     */
    public function setParent(\Entity\Collection $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Entity\Collection
     */
    public function getParent()
    {
        return $this->parent;
    }
    /**
     * @var boolean
     */
    private $showInBrowse;


    /**
     * Set showInBrowse
     *
     * @param boolean $showInBrowse
     *
     * @return Collection
     */
    public function setShowInBrowse($showInBrowse)
    {
        $this->showInBrowse = $showInBrowse;

        return $this;
    }

    /**
     * Get showInBrowse
     *
     * @return boolean
     */
    public function getShowInBrowse()
    {
        return $this->showInBrowse;
    }
    /**
     * @var string
     */
    private $collectionDescription;


    /**
     * Set collectionDescription
     *
     * @param string $collectionDescription
     *
     * @return Collection
     */
    public function setCollectionDescription($collectionDescription)
    {
        $this->collectionDescription = $collectionDescription;

        return $this;
    }

    /**
     * Get collectionDescription
     *
     * @return string
     */
    public function getCollectionDescription()
    {
        return $this->collectionDescription;
    }
}
