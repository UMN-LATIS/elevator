<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * JobLog
 *
 * @ORM\Table(name="job_logs", indexes={@ORM\Index(name="0", columns={"asset"})})
 * @ORM\Entity
 */
class JobLog
{
    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var string|null
     *
     * @ORM\Column(name="asset", type="string", nullable=true)
     */
    private $asset;

    /**
     * @var string|null
     *
     * @ORM\Column(name="type", type="string", nullable=true)
     */
    private $type;

    /**
     * @var int|null
     *
     * @ORM\Column(name="jobId", type="integer", nullable=true)
     */
    private $jobId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="task", type="string", nullable=true)
     */
    private $task;

    /**
     * @var string|null
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="job_logs_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;


}
