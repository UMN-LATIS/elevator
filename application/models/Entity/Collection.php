<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Collection
 *
 * @ORM\Table(name="collections")
 * @ORM\Entity
 */
class Collection
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="title", type="string", nullable=true)
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bucket", type="string", nullable=true)
     */
    private $bucket;

    /**
     * @var string|null
     *
     * @ORM\Column(name="s3Key", type="string", nullable=true)
     */
    private $s3Key;

    /**
     * @var string|null
     *
     * @ORM\Column(name="s3Secret", type="string", nullable=true)
     */
    private $s3Secret;

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
     * @var string|null
     *
     * @ORM\Column(name="bucketRegion", type="string", nullable=true)
     */
    private $bucketRegion;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="showInBrowse", type="boolean", nullable=true)
     */
    private $showInBrowse;

    /**
     * @var string|null
     *
     * @ORM\Column(name="collectionDescription", type="text", nullable=true)
     */
    private $collectionDescription;

    /**
     * @var string|null
     *
     * @ORM\Column(name="previewImage", type="text", nullable=true)
     */
    private $previewImage;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="collections_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\CollectionPermission", mappedBy="collection", cascade={"remove"})
     */
    private $permissions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\RecentCollection", mappedBy="collection", cascade={"remove"})
     */
    private $recent_collection;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\CSVBatch", mappedBy="collection")
     */
    private $csv_imports;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\Collection", mappedBy="parent")
     * @ORM\OrderBy({
     *     "title"="ASC"
     * })
     */
    private $children;

    /**
     * @var \Entity\Collection
     *
     * @ORM\ManyToOne(targetEntity="Entity\Collection", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Entity\Instance", mappedBy="collections")
     */
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

}
