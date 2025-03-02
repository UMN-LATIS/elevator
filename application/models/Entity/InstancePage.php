<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InstancePage
 *
 * @ORM\Table(name="instance_pages")
 * @ORM\Entity
 */
class InstancePage
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
     * @ORM\Column(name="body", type="text", nullable=true)
     */
    private $body;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="includeInHeader", type="boolean", nullable=true)
     */
    private $includeInHeader;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="modifiedAt", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * @var int|null
     *
     * @ORM\Column(name="sortOrder", type="integer", nullable=true)
     */
    private $sortOrder;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="instance_pages_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\InstancePage", mappedBy="parent", cascade={"persist","remove"})
     * @ORM\OrderBy({
     *     "sortOrder"="ASC"
     * })
     */
    private $children;

    /**
     * @var \Entity\Instance
     *
     * @ORM\ManyToOne(targetEntity="Entity\Instance", inversedBy="pages")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id")
     * })
     */
    private $instance;

    /**
     * @var \Entity\InstancePage
     *
     * @ORM\ManyToOne(targetEntity="Entity\InstancePage", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

}
