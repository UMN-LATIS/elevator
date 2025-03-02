<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Uploads
 *
 * @ORM\Table(name="uploads")
 * @ORM\Entity
 */
class Uploads
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="filename", type="string", nullable=true)
     */
    private $filename;

    /**
     * @var string|null
     *
     * @ORM\Column(name="filesize", type="string", nullable=true)
     */
    private $filesize;

    /**
     * @var string|null
     *
     * @ORM\Column(name="last_modified", type="string", nullable=true)
     */
    private $last_modified;

    /**
     * @var string|null
     *
     * @ORM\Column(name="chunks_uploaded", type="text", nullable=true)
     */
    private $chunks_uploaded;

    /**
     * @var string|null
     *
     * @ORM\Column(name="upload_id", type="string", nullable=true)
     */
    private $upload_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="uploadKey", type="string", nullable=true)
     */
    private $uploadKey;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="upload_start", type="datetime", nullable=true)
     */
    private $upload_start;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="last_information", type="datetime", nullable=true)
     */
    private $last_information;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="uploads_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;


}
