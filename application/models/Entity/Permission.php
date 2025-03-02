<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Permission
 */
#[ORM\Table(name: 'permissions')]
#[ORM\Entity]
class Permission
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'name', type: 'string', nullable: true)]
    private $name;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'label', type: 'string', nullable: true)]
    private $label;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'level', type: 'string', nullable: true)]
    private $level;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'createdAt', type: 'datetime', nullable: true)]
    private $createdAt;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'modifiedAt', type: 'datetime', nullable: true)]
    private $modifiedAt;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'permissions_id_seq', allocationSize: 1, initialValue: 1)]
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\InstancePermission::class, mappedBy: 'permission')]
    private $instances;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->instances = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * Set name.
     *
     * @param string|null $name
     *
     * @return Permission
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set label.
     *
     * @param string|null $label
     *
     * @return Permission
     */
    public function setLabel($label = null)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label.
     *
     * @return string|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set level.
     *
     * @param string|null $level
     *
     * @return Permission
     */
    public function setLevel($level = null)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level.
     *
     * @return string|null
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime|null $createdAt
     *
     * @return Permission
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
     * Set modifiedAt.
     *
     * @param \DateTime|null $modifiedAt
     *
     * @return Permission
     */
    public function setModifiedAt($modifiedAt = null)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * Get modifiedAt.
     *
     * @return \DateTime|null
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
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
     * Add instance.
     *
     * @param \Entity\InstancePermission $instance
     *
     * @return Permission
     */
    public function addInstance(\Entity\InstancePermission $instance)
    {
        $this->instances[] = $instance;

        return $this;
    }

    /**
     * Remove instance.
     *
     * @param \Entity\InstancePermission $instance
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeInstance(\Entity\InstancePermission $instance)
    {
        return $this->instances->removeElement($instance);
    }

    /**
     * Get instances.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInstances()
    {
        return $this->instances;
    }
}
