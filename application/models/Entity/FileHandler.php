<?php

namespace Entity;

/**
 * FileHandler
 */
class FileHandler
{
    /**
     * @var string
     */
    private $fileObjectId;

    /**
     * @var string
     */
    private $fileType;

    /**
     * @var string
     */
    private $handler;

    /**
     * @var string
     */
    private $parentObjectId;

    /**
     * @var integer
     */
    private $collectionId;

    /**
     * @var boolean
     */
    private $deleted;

    /**
     * @var array
     */
    private $globalMetadata;

    /**
     * @var array
     */
    private $sourceFile;

    /**
     * @var array
     */
    private $derivatives;

    /**
     * @var array
     */
    private $jobIdArray;

    /**
     * @var integer
     */
    private $id;


    /**
     * Set fileObjectId
     *
     * @param string $fileObjectId
     *
     * @return FileHandler
     */
    public function setFileObjectId($fileObjectId)
    {
        $this->fileObjectId = $fileObjectId;

        return $this;
    }

    /**
     * Get fileObjectId
     *
     * @return string
     */
    public function getFileObjectId()
    {
        return $this->fileObjectId;
    }

    /**
     * Set fileType
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
     * Get fileType
     *
     * @return string
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * Set handler
     *
     * @param string $handler
     *
     * @return FileHandler
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Get handler
     *
     * @return string
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Set parentObjectId
     *
     * @param string $parentObjectId
     *
     * @return FileHandler
     */
    public function setParentObjectId($parentObjectId)
    {
        $this->parentObjectId = $parentObjectId;

        return $this;
    }

    /**
     * Get parentObjectId
     *
     * @return string
     */
    public function getParentObjectId()
    {
        return $this->parentObjectId;
    }

    /**
     * Set collectionId
     *
     * @param integer $collectionId
     *
     * @return FileHandler
     */
    public function setCollectionId($collectionId)
    {
        $this->collectionId = $collectionId;

        return $this;
    }

    /**
     * Get collectionId
     *
     * @return integer
     */
    public function getCollectionId()
    {
        return $this->collectionId;
    }

    /**
     * Set deleted
     *
     * @param boolean $deleted
     *
     * @return FileHandler
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return boolean
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set globalMetadata
     *
     * @param array $globalMetadata
     *
     * @return FileHandler
     */
    public function setGlobalMetadata($globalMetadata)
    {
        $this->globalMetadata = $globalMetadata;

        return $this;
    }

    /**
     * Get globalMetadata
     *
     * @return array
     */
    public function getGlobalMetadata()
    {
        return $this->globalMetadata;
    }

    /**
     * Set sourceFile
     *
     * @param array $sourceFile
     *
     * @return FileHandler
     */
    public function setSourceFile($sourceFile)
    {
        $this->sourceFile = $sourceFile;

        return $this;
    }

    /**
     * Get sourceFile
     *
     * @return array
     */
    public function getSourceFile()
    {
        return $this->sourceFile;
    }

    /**
     * Set derivatives
     *
     * @param array $derivatives
     *
     * @return FileHandler
     */
    public function setDerivatives($derivatives)
    {
        $this->derivatives = $derivatives;

        return $this;
    }

    /**
     * Get derivatives
     *
     * @return array
     */
    public function getDerivatives()
    {
        return $this->derivatives;
    }

    /**
     * Set jobIdArray
     *
     * @param array $jobIdArray
     *
     * @return FileHandler
     */
    public function setJobIdArray($jobIdArray)
    {
        $this->jobIdArray = $jobIdArray;

        return $this;
    }

    /**
     * Get jobIdArray
     *
     * @return array
     */
    public function getJobIdArray()
    {
        return $this->jobIdArray;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
