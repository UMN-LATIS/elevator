<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DrawerItem
 *
 * @ORM\Table(name="drawer_items")
 * @ORM\Entity
 */
class DrawerItem
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="asset", type="string", nullable=true)
     */
    private $asset;

    /**
     * @var string|null
     *
     * @ORM\Column(name="excerptAsset", type="string", nullable=true)
     */
    private $excerptAsset;

    /**
     * @var float|null
     *
     * @ORM\Column(name="excerptStart", type="float", nullable=true)
     */
    private $excerptStart;

    /**
     * @var float|null
     *
     * @ORM\Column(name="excerptEnd", type="float", nullable=true)
     */
    private $excerptEnd;

    /**
     * @var string|null
     *
     * @ORM\Column(name="excerptLabel", type="string", nullable=true)
     */
    private $excerptLabel;

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
     * @ORM\SequenceGenerator(sequenceName="drawer_items_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Entity\Drawer
     *
     * @ORM\ManyToOne(targetEntity="Entity\Drawer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="drawer_id", referencedColumnName="id")
     * })
     */
    private $drawer;


}
