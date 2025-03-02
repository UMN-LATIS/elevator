<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DrawerItem
 */
#[ORM\Table(name: 'drawer_items')]
#[ORM\Entity]
class DrawerItem
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'asset', type: 'string', nullable: true)]
    private $asset;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'excerptAsset', type: 'string', nullable: true)]
    private $excerptAsset;

    /**
     * @var float|null
     */
    #[ORM\Column(name: 'excerptStart', type: 'float', nullable: true)]
    private $excerptStart;

    /**
     * @var float|null
     */
    #[ORM\Column(name: 'excerptEnd', type: 'float', nullable: true)]
    private $excerptEnd;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'excerptLabel', type: 'string', nullable: true)]
    private $excerptLabel;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'sortOrder', type: 'integer', nullable: true)]
    private $sortOrder;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'drawer_items_id_seq', allocationSize: 1, initialValue: 1)]
    private $id;

    /**
     * @var \Entity\Drawer
     */
    #[ORM\JoinColumn(name: 'drawer_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\Drawer::class)]
    private $drawer;



    /**
     * Set asset.
     *
     * @param string|null $asset
     *
     * @return DrawerItem
     */
    public function setAsset($asset = null)
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * Get asset.
     *
     * @return string|null
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * Set excerptAsset.
     *
     * @param string|null $excerptAsset
     *
     * @return DrawerItem
     */
    public function setExcerptAsset($excerptAsset = null)
    {
        $this->excerptAsset = $excerptAsset;

        return $this;
    }

    /**
     * Get excerptAsset.
     *
     * @return string|null
     */
    public function getExcerptAsset()
    {
        return $this->excerptAsset;
    }

    /**
     * Set excerptStart.
     *
     * @param float|null $excerptStart
     *
     * @return DrawerItem
     */
    public function setExcerptStart($excerptStart = null)
    {
        $this->excerptStart = $excerptStart;

        return $this;
    }

    /**
     * Get excerptStart.
     *
     * @return float|null
     */
    public function getExcerptStart()
    {
        return $this->excerptStart;
    }

    /**
     * Set excerptEnd.
     *
     * @param float|null $excerptEnd
     *
     * @return DrawerItem
     */
    public function setExcerptEnd($excerptEnd = null)
    {
        $this->excerptEnd = $excerptEnd;

        return $this;
    }

    /**
     * Get excerptEnd.
     *
     * @return float|null
     */
    public function getExcerptEnd()
    {
        return $this->excerptEnd;
    }

    /**
     * Set excerptLabel.
     *
     * @param string|null $excerptLabel
     *
     * @return DrawerItem
     */
    public function setExcerptLabel($excerptLabel = null)
    {
        $this->excerptLabel = $excerptLabel;

        return $this;
    }

    /**
     * Get excerptLabel.
     *
     * @return string|null
     */
    public function getExcerptLabel()
    {
        return $this->excerptLabel;
    }

    /**
     * Set sortOrder.
     *
     * @param int|null $sortOrder
     *
     * @return DrawerItem
     */
    public function setSortOrder($sortOrder = null)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * Get sortOrder.
     *
     * @return int|null
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
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
     * Set drawer.
     *
     * @param \Entity\Drawer|null $drawer
     *
     * @return DrawerItem
     */
    public function setDrawer(?\Entity\Drawer $drawer = null)
    {
        $this->drawer = $drawer;

        return $this;
    }

    /**
     * Get drawer.
     *
     * @return \Entity\Drawer|null
     */
    public function getDrawer()
    {
        return $this->drawer;
    }
}
