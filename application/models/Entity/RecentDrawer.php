<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RecentDrawer
 */
#[ORM\Table(name: 'recent_drawer')]
#[ORM\Entity]
class RecentDrawer
{
    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'createdAt', type: 'datetime', nullable: true)]
    private $createdAt;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var \Entity\User
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\User::class, inversedBy: 'recent_drawers')]
    private $user;

    /**
     * @var \Entity\Drawer
     */
    #[ORM\JoinColumn(name: 'drawer_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\Drawer::class)]
    private $drawer;

    /**
     * @var \Entity\Instance
     */
    #[ORM\JoinColumn(name: 'instance_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\Instance::class)]
    private $instance;



    /**
     * Set createdAt.
     *
     * @param \DateTime|null $createdAt
     *
     * @return RecentDrawer
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user.
     *
     * @param \Entity\User|null $user
     *
     * @return RecentDrawer
     */
    public function setUser(?\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \Entity\User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set drawer.
     *
     * @param \Entity\Drawer|null $drawer
     *
     * @return RecentDrawer
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

    /**
     * Set instance.
     *
     * @param \Entity\Instance|null $instance
     *
     * @return RecentDrawer
     */
    public function setInstance(?\Entity\Instance $instance = null)
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * Get instance.
     *
     * @return \Entity\Instance|null
     */
    public function getInstance()
    {
        return $this->instance;
    }
}
