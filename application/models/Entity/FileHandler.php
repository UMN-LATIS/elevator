<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FileHandler
 *
 * @ORM\Table(name="filehandlers", indexes={@ORM\Index(name="0", columns={"fileObjectId"}), @ORM\Index(name="1", columns={"parentObjectId"})})
 * @ORM\Entity
 */
class FileHandler
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="fileObjectId", type="string", nullable=true)
     */
    private $fileObjectId;

    /**
     * @var string
     *
     * @ORM\Column(name="fileType", type="string")
     */
    private $fileType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="handler", type="string", nullable=true)
     */
    private $handler;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parentObjectId", type="string", nullable=true)
     */
    private $parentObjectId;

    /**
     * @var int
     *
     * @ORM\Column(name="collectionId", type="integer")
     */
    private $collectionId;

    /**
     * @var bool
     *
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted;

    /**
     * @var array|null
     *
     * @ORM\Column(name="globalMetadata", type="json_array", nullable=true)
     */
    private $globalMetadata;

    /**
     * @var array|null
     *
     * @ORM\Column(name="sourceFile", type="json_array", nullable=true)
     */
    private $sourceFile;

    /**
     * @var array|null
     *
     * @ORM\Column(name="derivatives", type="json_array", nullable=true)
     */
    private $derivatives;

    /**
     * @var array|null
     *
     * @ORM\Column(name="jobIdArray", type="json_array", nullable=true)
     */
    private $jobIdArray;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="filehandlers_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;


}
