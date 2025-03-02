<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LTI13InstanceAssociation
 *
 * @ORM\Table(name="lti13_instance_association")
 * @ORM\Entity
 */
class LTI13InstanceAssociation
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="lms_course", type="string", nullable=true)
     */
    private $lms_course;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="lti13_instance_association_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Entity\Instance
     *
     * @ORM\ManyToOne(targetEntity="Entity\Instance")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instance_id", referencedColumnName="id")
     * })
     */
    private $instance;

    /**
     * @var \Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Entity\User", inversedBy="lti_courses")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;


}
