<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RecentDrawer
 *
 * @ORM\Table(name="recent_drawer")
 * @ORM\Entity
 */
class RecentDrawer
{
    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="recent_drawer_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Entity\User", inversedBy="recent_drawers")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

    /**
     * @var \Entity\Drawer
     *
     * @ORM\ManyToOne(targetEntity="Entity\Drawer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="drawer_id", referencedColumnName="id")
     * })
     */
    private $drawer;

    /**
     * @var \Entity\Instance
     *
     * @ORM\ManyToOne(targetEntity="Entity\Instance")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id")
     * })
     */
    private $instance;


}
