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

}
