<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SearchEntry
 */
#[ORM\Table(name: 'searches')]
#[ORM\Entity]
class SearchEntry
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'searchText', type: 'text', nullable: true)]
    private $searchText;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'searchData', type: 'json_array', nullable: true)]
    private $searchData;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'userInitiated', type: 'boolean', nullable: false)]
    private $userInitiated;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'createdAt', type: 'datetime', nullable: true)]
    private $createdAt;

    /**
     * @var string
     */
    #[ORM\Column(name: 'id', type: 'guid')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'UUID')]
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
    #[ORM\JoinColumn(name: 'instance_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \Entity\Instance::class)]
    private $instance;



    /**
     * Set searchText.
     *
     * @param string|null $searchText
     *
     * @return SearchEntry
     */
    public function setSearchText($searchText = null)
    {
        $this->searchText = $searchText;

        return $this;
    }

    /**
     * Get searchText.
     *
     * @return string|null
     */
    public function getSearchText()
    {
        return $this->searchText;
    }

    /**
     * Set searchData.
     *
     * @param array|null $searchData
     *
     * @return SearchEntry
     */
    public function setSearchData($searchData = null)
    {
        $this->searchData = $searchData;

        return $this;
    }

    /**
     * Get searchData.
     *
     * @return array|null
     */
    public function getSearchData()
    {
        return $this->searchData;
    }

    /**
     * Set userInitiated.
     *
     * @param bool $userInitiated
     *
     * @return SearchEntry
     */
    public function setUserInitiated($userInitiated)
    {
        $this->userInitiated = $userInitiated;

        return $this;
    }

    /**
     * Get userInitiated.
     *
     * @return bool
     */
    public function getUserInitiated()
    {
        return $this->userInitiated;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime|null $createdAt
     *
     * @return SearchEntry
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
     * @return string
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
     * @return SearchEntry
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
     * @return SearchEntry
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
