<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DrawerItem
 */
class DrawerItem
{
    /**
     * @var string
     */
    private $asset;

    /**
     * @var string
     */
    private $excerptAsset;

    /**
     * @var float
     */
    private $excerptStart;

    /**
     * @var float
     */
    private $excerptEnd;

    /**
     * @var string
     */
    private $excerptLabel;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Entity\Drawer
     */
    private $drawer;


    /**
     * Set asset
     *
     * @param string $asset
     * @return DrawerItem
     */
    public function setAsset($asset)
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * Get asset
     *
     * @return string 
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * Set excerptAsset
     *
     * @param string $excerptAsset
     * @return DrawerItem
     */
    public function setExcerptAsset($excerptAsset)
    {
        $this->excerptAsset = $excerptAsset;

        return $this;
    }

    /**
     * Get excerptAsset
     *
     * @return string 
     */
    public function getExcerptAsset()
    {
        return $this->excerptAsset;
    }

    /**
     * Set excerptStart
     *
     * @param float $excerptStart
     * @return DrawerItem
     */
    public function setExcerptStart($excerptStart)
    {
        $this->excerptStart = $excerptStart;

        return $this;
    }

    /**
     * Get excerptStart
     *
     * @return float 
     */
    public function getExcerptStart()
    {
        return $this->excerptStart;
    }

    /**
     * Set excerptEnd
     *
     * @param float $excerptEnd
     * @return DrawerItem
     */
    public function setExcerptEnd($excerptEnd)
    {
        $this->excerptEnd = $excerptEnd;

        return $this;
    }

    /**
     * Get excerptEnd
     *
     * @return float 
     */
    public function getExcerptEnd()
    {
        return $this->excerptEnd;
    }

    /**
     * Set excerptLabel
     *
     * @param string $excerptLabel
     * @return DrawerItem
     */
    public function setExcerptLabel($excerptLabel)
    {
        $this->excerptLabel = $excerptLabel;

        return $this;
    }

    /**
     * Get excerptLabel
     *
     * @return string 
     */
    public function getExcerptLabel()
    {
        return $this->excerptLabel;
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
     * Set drawer
     *
     * @param \Entity\Drawer $drawer
     * @return DrawerItem
     */
    public function setDrawer(\Entity\Drawer $drawer = null)
    {
        $this->drawer = $drawer;

        return $this;
    }

    /**
     * Get drawer
     *
     * @return \Entity\Drawer 
     */
    public function getDrawer()
    {
        return $this->drawer;
    }
}
