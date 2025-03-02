<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FileHandler
 */
#[ORM\Table(name: 'filehandlers')]
#[ORM\Index(name: 0, columns: ['fileObjectId'])]
#[ORM\Index(name: 1, columns: ['parentObjectId'])]
#[ORM\Entity]
class FileHandler
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'fileObjectId', type: 'string', nullable: true)]
    private $fileObjectId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'fileType', type: 'string')]
    private $fileType;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'handler', type: 'string', nullable: true)]
    private $handler;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'parentObjectId', type: 'string', nullable: true)]
    private $parentObjectId;

    /**
     * @var int
     */
    #[ORM\Column(name: 'collectionId', type: 'integer')]
    private $collectionId;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'deleted', type: 'boolean')]
    private $deleted;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'globalMetadata', type: 'json', nullable: true)]
    private $globalMetadata;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'sourceFile', type: 'json', nullable: true)]
    private $sourceFile;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'derivatives', type: 'json', nullable: true)]
    private $derivatives;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'jobIdArray', type: 'json', nullable: true)]
    private $jobIdArray;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'filehandlers_id_seq', allocationSize: 1, initialValue: 1)]
    private $id;



    /**
     * Set fileObjectId.
     *
     * @param string|null $fileObjectId
     *
     * @return FileHandler
     */
    public function setFileObjectId($fileObjectId = null)
    {
        $this->fileObjectId = $fileObjectId;

        return $this;
    }

    /**
     * Get fileObjectId.
     *
     * @return string|null
     */
    public function getFileObjectId()
    {
        return $this->fileObjectId;
    }

    /**
     * Set fileType.
     *
     * @param string $fileType
     *
     * @return FileHandler
     */
    public function setFileType($fileType)
    {
        $this->fileType = $fileType;

        return $this;
    }

    /**
     * Get fileType.
     *
     * @return string
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * Set handler.
     *
     * @param string|null $handler
     *
     * @return FileHandler
     */
    public function setHandler($handler = null)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Get handler.
     *
     * @return string|null
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Set parentObjectId.
     *
     * @param string|null $parentObjectId
     *
     * @return FileHandler
     */
    public function setParentObjectId($parentObjectId = null)
    {
        $this->parentObjectId = $parentObjectId;

        return $this;
    }

    /**
     * Get parentObjectId.
     *
     * @return string|null
     */
    public function getParentObjectId()
    {
        return $this->parentObjectId;
    }

    /**
     * Set collectionId.
     *
     * @param int $collectionId
     *
     * @return FileHandler
     */
    public function setCollectionId($collectionId)
    {
        $this->collectionId = $collectionId;

        return $this;
    }

    /**
     * Get collectionId.
     *
     * @return int
     */
    public function getCollectionId()
    {
        return $this->collectionId;
    }

    /**
     * Set deleted.
     *
     * @param bool $deleted
     *
     * @return FileHandler
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted.
     *
     * @return bool
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set globalMetadata.
     *
     * @param array|null $globalMetadata
     *
     * @return FileHandler
     */
    public function setGlobalMetadata($globalMetadata = null)
    {
        $this->globalMetadata = $globalMetadata;

        return $this;
    }

    /**
     * Get globalMetadata.
     *
     * @return array|null
     */
    public function getGlobalMetadata()
    {
        return $this->globalMetadata;
    }

    /**
     * Set sourceFile.
     *
     * @param array|null $sourceFile
     *
     * @return FileHandler
     */
    public function setSourceFile($sourceFile = null)
    {
        $this->sourceFile = $sourceFile;

        return $this;
    }

    /**
     * Get sourceFile.
     *
     * @return array|null
     */
    public function getSourceFile()
    {
        return $this->sourceFile;
    }

    /**
     * Set derivatives.
     *
     * @param array|null $derivatives
     *
     * @return FileHandler
     */
    public function setDerivatives($derivatives = null)
    {
        $this->derivatives = $derivatives;

        return $this;
    }

    /**
     * Get derivatives.
     *
     * @return array|null
     */
    public function getDerivatives()
    {
        return $this->derivatives;
    }

    /**
     * Set jobIdArray.
     *
     * @param array|null $jobIdArray
     *
     * @return FileHandler
     */
    public function setJobIdArray($jobIdArray = null)
    {
        $this->jobIdArray = $jobIdArray;

        return $this;
    }

    /**
     * Get jobIdArray.
     *
     * @return array|null
     */
    public function getJobIdArray()
    {
        return $this->jobIdArray;
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
}
