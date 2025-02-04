<?php

namespace Entity;

/**
 * CSVBatch
 */
class CSVBatch
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var int
     */
    private $id;


    /**
     * Set filename.
     *
     * @param string $filename
     *
     * @return CSVBatch
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return CSVBatch
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
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
     * @var \Entity\Collection
     */
    private $collection;

    /**
     * @var \Entity\Template
     */
    private $template;

    /**
     * @var \Entity\User
     */
    private $createdBy;


    /**
     * Set collection.
     *
     * @param \Entity\Collection|null $collection
     *
     * @return CSVBatch
     */
    public function setCollection(?\Entity\Collection $collection = null)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Get collection.
     *
     * @return \Entity\Collection|null
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Set template.
     *
     * @param \Entity\Template|null $template
     *
     * @return CSVBatch
     */
    public function setTemplate(?\Entity\Template $template = null)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template.
     *
     * @return \Entity\Template|null
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set createdBy.
     *
     * @param \Entity\User|null $createdBy
     *
     * @return CSVBatch
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
     * @var \Doctrine\Common\Collections\Collection
     */
    private $assets;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->assets = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add asset.
     *
     * @param \Entity\Asset $asset
     *
     * @return CSVBatch
     */
    public function addAsset(\Entity\Asset $asset)
    {
        $this->assets[] = $asset;

        return $this;
    }

    /**
     * Remove asset.
     *
     * @param \Entity\Asset $asset
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeAsset(\Entity\Asset $asset)
    {
        return $this->assets->removeElement($asset);
    }

    /**
     * Get assets.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAssets()
    {
        return $this->assets;
    }
}
