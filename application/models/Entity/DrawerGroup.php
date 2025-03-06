<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DrawerGroup
 */
#[ORM\Table(name: 'drawer_groups')]
#[ORM\Entity]
class DrawerGroup
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'group_type', type: 'string', nullable: true)]
    private $group_type;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'group_value', type: 'string', nullable: true)]
    private $group_value;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'group_label', type: 'string', nullable: true)]
    private $group_label;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'expiration', type: 'datetime', nullable: true)]
    private $expiration;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'drawer_groups_id_seq', allocationSize: 1, initialValue: 1)]
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \Entity\DrawerPermission::class, mappedBy: 'group', cascade: ['remove'])]
    private $permissions;

    /**
     * @var \Entity\User
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\User::class)]
    private $user;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\JoinTable(name: 'drawergroup_drawer')]
    #[ORM\JoinColumn(name: 'drawergroup_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'drawer_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\ManyToMany(targetEntity: \Entity\Drawer::class)]
    private $drawer = array();

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\JoinTable(name: 'drawergroup_groupentry')]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'entry_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ORM\ManyToMany(targetEntity: \Entity\GroupEntry::class, inversedBy: 'group', cascade: ['persist'], orphanRemoval: true)]
    private $group_values = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->drawer = new \Doctrine\Common\Collections\ArrayCollection();
        $this->group_values = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * Set groupType.
     *
     * @param string|null $groupType
     *
     * @return DrawerGroup
     */
    public function setGroupType($groupType = null)
    {
        $this->group_type = $groupType;

        return $this;
    }

    /**
     * Get groupType.
     *
     * @return string|null
     */
    public function getGroupType()
    {
        return $this->group_type;
    }

    /**
     * Set groupValue.
     *
     * @param string|null $groupValue
     *
     * @return DrawerGroup
     */
    public function setGroupValue($groupValue = null)
    {
        $this->group_value = $groupValue;

        return $this;
    }

    /**
     * Get groupValue.
     *
     * @return string|null
     */
    public function getGroupValue()
    {
        return $this->group_value;
    }

    /**
     * Set groupLabel.
     *
     * @param string|null $groupLabel
     *
     * @return DrawerGroup
     */
    public function setGroupLabel($groupLabel = null)
    {
        $this->group_label = $groupLabel;

        return $this;
    }

    /**
     * Get groupLabel.
     *
     * @return string|null
     */
    public function getGroupLabel()
    {
        return $this->group_label;
    }

    /**
     * Set expiration.
     *
     * @param \DateTime|null $expiration
     *
     * @return DrawerGroup
     */
    public function setExpiration($expiration = null)
    {
        $this->expiration = $expiration;

        return $this;
    }

    /**
     * Get expiration.
     *
     * @return \DateTime|null
     */
    public function getExpiration()
    {
        return $this->expiration;
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
     * Add permission.
     *
     * @param \Entity\DrawerPermission $permission
     *
     * @return DrawerGroup
     */
    public function addPermission(\Entity\DrawerPermission $permission)
    {
        $this->permissions[] = $permission;

        return $this;
    }

    /**
     * Remove permission.
     *
     * @param \Entity\DrawerPermission $permission
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePermission(\Entity\DrawerPermission $permission)
    {
        return $this->permissions->removeElement($permission);
    }

    /**
     * Get permissions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Set user.
     *
     * @param \Entity\User|null $user
     *
     * @return DrawerGroup
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
     * Add drawer.
     *
     * @param \Entity\Drawer $drawer
     *
     * @return DrawerGroup
     */
    public function addDrawer(\Entity\Drawer $drawer)
    {
        $drawer->addGroup($this);
        $this->drawer[] = $drawer;

        return $this;
    }

    /**
     * Remove drawer.
     *
     * @param \Entity\Drawer $drawer
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeDrawer(\Entity\Drawer $drawer)
    {
        return $this->drawer->removeElement($drawer);
    }

    /**
     * Get drawer.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDrawer()
    {
        return $this->drawer;
    }

    /**
     * Add groupValue.
     *
     * @param \Entity\GroupEntry $groupValue
     *
     * @return DrawerGroup
     */
    public function addGroupValue(\Entity\GroupEntry $groupValue)
    {

        $this->group_values[] = $groupValue;

        return $this;
    }

    /**
     * Remove groupValue.
     *
     * @param \Entity\GroupEntry $groupValue
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeGroupValue(\Entity\GroupEntry $groupValue)
    {
        return $this->group_values->removeElement($groupValue);
    }

    /**
     * Get groupValues.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroupValues()
    {
        return $this->group_values;
    }
}
