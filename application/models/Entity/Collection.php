<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Collection
 */
#[ORM\Table(name: 'collections')]
#[ORM\Entity]
class Collection
{
    /**
     * Elevator addition
     * @var \Application\Models\Filehandlers\Filehandlerbase|null
     */
    public $previewImageHandler = null;


    /**
     * @var string|null
     */
    #[ORM\Column(name: 'title', type: 'string', nullable: true)]
    private $title;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'bucket', type: 'string', nullable: true)]
    private $bucket;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 's3Key', type: 'string', nullable: true)]
    private $s3Key;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 's3Secret', type: 'string', nullable: true)]
    private $s3Secret;

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
     * @var string|null
     */
    #[ORM\Column(name: 'bucketRegion', type: 'string', nullable: true)]
    private $bucketRegion;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'showInBrowse', type: 'boolean', nullable: true)]
    private $showInBrowse;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'collectionDescription', type: 'text', nullable: true)]
    private $collectionDescription;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'previewImage', type: 'text', nullable: true)]
    private $previewImage;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'collections_id_seq', allocationSize: 1, initialValue: 1)]
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\CollectionPermission::class, mappedBy: 'collection', cascade: ['remove'])]
    private $permissions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\RecentCollection::class, mappedBy: 'collection', cascade: ['remove'])]
    private $recent_collection;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\CSVBatch::class, mappedBy: 'collection')]
    private $csv_imports;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\Collection::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['title' => 'ASC'])]
    private $children;

    /**
     * @var \Entity\Collection
     */
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\Collection::class, inversedBy: 'children')]
    private $parent;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\ManyToMany(targetEntity: \Entity\Instance::class, mappedBy: 'collections')]
    private $instances = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->recent_collection = new \Doctrine\Common\Collections\ArrayCollection();
        $this->csv_imports = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->instances = new \Doctrine\Common\Collections\ArrayCollection();
    }


    
    /**
     * Has Children
     * @return boolean
     */
    public function hasChildren() {
        if(!$this->children) { 
            return false;
        }
        return !$this->children->isEmpty();
    }

    public function hasBrowseableChildren() {
        $filteredArray = array_filter($this->children->toArray(), function($n) { return $n->getShowInBrowse();});
        return count($filteredArray)>0?true:false;
    }

    public function getFlattenedChildren() {
        $outputArray = array();
        if(!$this->hasChildren()) {
            return $outputArray;
        }
        foreach($this->children as $child) {
            $outputArray[] = $child;
            if($child->hasChildren()) {
                $outputArray = array_merge($outputArray, $child->getFlattenedChildren());
            }
        }
        return $outputArray;
    }


    /**
     * Set title.
     *
     * @param string|null $title
     *
     * @return Collection
     */
    public function setTitle($title = null)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set bucket.
     *
     * @param string|null $bucket
     *
     * @return Collection
     */
    public function setBucket($bucket = null)
    {
        $this->bucket = $bucket;

        return $this;
    }

    /**
     * Get bucket.
     *
     * @return string|null
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Set s3Key.
     *
     * @param string|null $s3Key
     *
     * @return Collection
     */
    public function setS3Key($s3Key = null)
    {
        $this->s3Key = $s3Key;

        return $this;
    }

    /**
     * Get s3Key.
     *
     * @return string|null
     */
    public function getS3Key()
    {
        return $this->s3Key;
    }

    /**
     * Set s3Secret.
     *
     * @param string|null $s3Secret
     *
     * @return Collection
     */
    public function setS3Secret($s3Secret = null)
    {
        $this->s3Secret = $s3Secret;

        return $this;
    }

    /**
     * Get s3Secret.
     *
     * @return string|null
     */
    public function getS3Secret()
    {
        return $this->s3Secret;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime|null $createdAt
     *
     * @return Collection
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
     * @return Collection
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
     * Set bucketRegion.
     *
     * @param string|null $bucketRegion
     *
     * @return Collection
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
     * Set showInBrowse.
     *
     * @param bool|null $showInBrowse
     *
     * @return Collection
     */
    public function setShowInBrowse($showInBrowse = null)
    {
        $this->showInBrowse = $showInBrowse;

        return $this;
    }

    /**
     * Get showInBrowse.
     *
     * @return bool|null
     */
    public function getShowInBrowse()
    {
        return $this->showInBrowse;
    }

    /**
     * Set collectionDescription.
     *
     * @param string|null $collectionDescription
     *
     * @return Collection
     */
    public function setCollectionDescription($collectionDescription = null)
    {
        $this->collectionDescription = $collectionDescription;

        return $this;
    }

    /**
     * Get collectionDescription.
     *
     * @return string|null
     */
    public function getCollectionDescription()
    {
        return $this->collectionDescription;
    }

    /**
     * Set previewImage.
     *
     * @param string|null $previewImage
     *
     * @return Collection
     */
    public function setPreviewImage($previewImage = null)
    {
        $this->previewImage = $previewImage;

        return $this;
    }

    /**
     * Get previewImage.
     *
     * @return string|null
     */
    public function getPreviewImage()
    {
        return $this->previewImage;
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
     * @param \Entity\CollectionPermission $permission
     *
     * @return Collection
     */
    public function addPermission(\Entity\CollectionPermission $permission)
    {
        $this->permissions[] = $permission;

        return $this;
    }

    /**
     * Remove permission.
     *
     * @param \Entity\CollectionPermission $permission
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePermission(\Entity\CollectionPermission $permission)
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
     * Add recentCollection.
     *
     * @param \Entity\RecentCollection $recentCollection
     *
     * @return Collection
     */
    public function addRecentCollection(\Entity\RecentCollection $recentCollection)
    {
        $this->recent_collection[] = $recentCollection;

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
        return $this->recent_collection->removeElement($recentCollection);
    }

    /**
     * Get recentCollection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecentCollection()
    {
        return $this->recent_collection;
    }

    /**
     * Add csvImport.
     *
     * @param \Entity\CSVBatch $csvImport
     *
     * @return Collection
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
     * Add child.
     *
     * @param \Entity\Collection $child
     *
     * @return Collection
     */
    public function addChild(\Entity\Collection $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child.
     *
     * @param \Entity\Collection $child
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeChild(\Entity\Collection $child)
    {
        return $this->children->removeElement($child);
    }

    /**
     * Get children.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent.
     *
     * @param \Entity\Collection|null $parent
     *
     * @return Collection
     */
    public function setParent(?\Entity\Collection $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return \Entity\Collection|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add instance.
     *
     * @param \Entity\Instance $instance
     *
     * @return Collection
     */
    public function addInstance(\Entity\Instance $instance)
    {
        $this->instances[] = $instance;

        return $this;
    }

    /**
     * Remove instance.
     *
     * @param \Entity\Instance $instance
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeInstance(\Entity\Instance $instance)
    {
        return $this->instances->removeElement($instance);
    }

    /**
     * Get instances.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInstances()
    {
        return $this->instances;
    }
}
