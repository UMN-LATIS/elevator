<?php

namespace Entity;

/**
 * LTI13InstanceAssociation
 */
class LTI13InstanceAssociation
{
    /**
     * @var string|null
     */
    private $lms_course;

    /**
     * @var int
     */
    private $id;

    /**
     * @var \Entity\Instance
     */
    private $instance;

    /**
     * @var \Entity\User
     */
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
    public function setInstance(\Entity\Instance $instance = null)
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
    public function setUser(\Entity\User $user = null)
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
