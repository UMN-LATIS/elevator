<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CustomSearch
 */
#[ORM\Table(name: 'custom_search')]
#[ORM\Entity]
class CustomSearch
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'searchConfig', type: 'text', nullable: true)]
    private $searchConfig;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'searchTitle', type: 'string', nullable: true)]
    private $searchTitle;

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
    #[ORM\ManyToOne(targetEntity: \Entity\User::class)]
    private $user;

    /**
     * @var \Entity\Instance
     */
    #[ORM\JoinColumn(name: 'instance_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\Instance::class)]
    private $instance;



    /**
     * Set searchConfig.
     *
     * @param string|null $searchConfig
     *
     * @return CustomSearch
     */
    public function setSearchConfig($searchConfig = null)
    {
        $this->searchConfig = $searchConfig;

        return $this;
    }

    /**
     * Get searchConfig.
     *
     * @return string|null
     */
    public function getSearchConfig()
    {
        return $this->searchConfig;
    }

    /**
     * Set searchTitle.
     *
     * @param string|null $searchTitle
     *
     * @return CustomSearch
     */
    public function setSearchTitle($searchTitle = null)
    {
        $this->searchTitle = $searchTitle;

        return $this;
    }

    /**
     * Get searchTitle.
     *
     * @return string|null
     */
    public function getSearchTitle()
    {
        return $this->searchTitle;
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
     * @return CustomSearch
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
     * Set instance.
     *
     * @param \Entity\Instance|null $instance
     *
     * @return CustomSearch
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
