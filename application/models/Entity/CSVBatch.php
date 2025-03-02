<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CSVBatch
 *
 * @ORM\Table(name="csv_batches")
 * @ORM\Entity
 */
class CSVBatch
{
    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", nullable=false)
     */
    private $filename;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    private $createdAt = 'CURRENT_TIMESTAMP';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="csv_batches_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\Asset", mappedBy="csvImport", fetch="EXTRA_LAZY")
     */
    private $assets;

    /**
     * @var \Entity\Collection
     *
     * @ORM\ManyToOne(targetEntity="Entity\Collection", inversedBy="csvImports")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="collection_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $collection;

    /**
     * @var \Entity\Template
     *
     * @ORM\ManyToOne(targetEntity="Entity\Template", inversedBy="csvImports")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="template_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $template;

    /**
     * @var \Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Entity\User", inversedBy="csvImports")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="createdby_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $createdBy;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->assets = new \Doctrine\Common\Collections\ArrayCollection();
    }


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
}
