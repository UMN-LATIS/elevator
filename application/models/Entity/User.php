<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Table(name="users", indexes={@ORM\Index(name="0", columns={"username"}), @ORM\Index(name="1", columns={"emplid"})})
 * @ORM\Entity
 */
class User
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="emplid", type="string", nullable=true)
     */
    private $emplid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="username", type="string", nullable=true)
     */
    private $username;

    /**
     * @var string|null
     *
     * @ORM\Column(name="userType", type="string", nullable=true)
     */
    private $userType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", nullable=true)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="displayName", type="string", nullable=true)
     */
    private $displayName;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="fastUpload", type="boolean", nullable=true)
     */
    private $fastUpload;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password", type="string", nullable=true)
     */
    private $password;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="isSuperAdmin", type="boolean", nullable=true)
     */
    private $isSuperAdmin;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="hasExpiry", type="boolean", nullable=true)
     */
    private $hasExpiry;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="expires", type="datetime", nullable=true)
     */
    private $expires;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="modifiedAt", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="users_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\RecentDrawer", mappedBy="user", cascade={"persist","remove"}, orphanRemoval=true)
     * @ORM\OrderBy({
     *     "createdAt"="ASC"
     * })
     */
    private $recent_drawers;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\SearchEntry", mappedBy="user", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $recent_searches;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\RecentCollection", mappedBy="user", cascade={"persist","remove"}, orphanRemoval=true)
     * @ORM\OrderBy({
     *     "createdAt"="ASC"
     * })
     */
    private $recent_collections;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\CSVBatch", mappedBy="createdBy")
     */
    private $csv_imports;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\ApiKey", mappedBy="owner", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $api_keys;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\LTI13InstanceAssociation", mappedBy="user", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $lti_courses;

    /**
     * @var \Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="createdBy_id", referencedColumnName="id")
     * })
     */
    private $createdBy;

    /**
     * @var \Entity\Instance
     *
     * @ORM\ManyToOne(targetEntity="Entity\Instance")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $instance;

    /**
     * @var \Entity\Instance
     *
     * @ORM\ManyToOne(targetEntity="Entity\Instance")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="apiInstance_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $apiInstance;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->recent_drawers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->recent_searches = new \Doctrine\Common\Collections\ArrayCollection();
        $this->recent_collections = new \Doctrine\Common\Collections\ArrayCollection();
        $this->csv_imports = new \Doctrine\Common\Collections\ArrayCollection();
        $this->api_keys = new \Doctrine\Common\Collections\ArrayCollection();
        $this->lti_courses = new \Doctrine\Common\Collections\ArrayCollection();
    }

}
