<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LTI13InstanceAssociation
 */
#[ORM\Table(name: 'lti13_instance_association')]
#[ORM\Entity]
class LTI13InstanceAssociation
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'lms_course', type: 'string', nullable: true)]
    private $lms_course;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var \Entity\Instance
     */
    #[ORM\JoinColumn(name: 'instance_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\Instance::class)]
    private $instance;

    /**
     * @var \Entity\User
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\User::class, inversedBy: 'lti_courses')]
    private $user;



    /**
     * Set lmsCourse.
     *
     * @param string|null $lmsCourse
     *
     * @return LTI13InstanceAssociation
     */
    public function setLmsCourse($lmsCourse = null)
    {
        $this->lms_course = $lmsCourse;

        return $this;
    }

    /**
     * Get lmsCourse.
     *
     * @return string|null
     */
    public function getLmsCourse()
    {
        return $this->lms_course;
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
     * Set instance.
     *
     * @param \Entity\Instance|null $instance
     *
     * @return LTI13InstanceAssociation
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

    /**
     * Set user.
     *
     * @param \Entity\User|null $user
     *
     * @return LTI13InstanceAssociation
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
}
