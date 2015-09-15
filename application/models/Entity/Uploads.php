<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Uploads
 */
class Uploads
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var string
     */
    private $filesize;

    /**
     * @var string
     */
    private $last_modified;

    /**
     * @var string
     */
    private $chunks_uploaded;

    /**
     * @var string
     */
    private $upload_id;

    /**
     * @var string
     */
    private $uploadKey;

    /**
     * @var \DateTime
     */
    private $upload_start;

    /**
     * @var \DateTime
     */
    private $last_information;

    /**
     * @var integer
     */
    private $id;


    /**
     * Set filename
     *
     * @param string $filename
     * @return Uploads
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string 
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set filesize
     *
     * @param string $filesize
     * @return Uploads
     */
    public function setFilesize($filesize)
    {
        $this->filesize = $filesize;

        return $this;
    }

    /**
     * Get filesize
     *
     * @return string 
     */
    public function getFilesize()
    {
        return $this->filesize;
    }

    /**
     * Set last_modified
     *
     * @param string $lastModified
     * @return Uploads
     */
    public function setLastModified($lastModified)
    {
        $this->last_modified = $lastModified;

        return $this;
    }

    /**
     * Get last_modified
     *
     * @return string 
     */
    public function getLastModified()
    {
        return $this->last_modified;
    }

    /**
     * Set chunks_uploaded
     *
     * @param string $chunksUploaded
     * @return Uploads
     */
    public function setChunksUploaded($chunksUploaded)
    {
        $this->chunks_uploaded = $chunksUploaded;

        return $this;
    }

    /**
     * Get chunks_uploaded
     *
     * @return string 
     */
    public function getChunksUploaded()
    {
        return $this->chunks_uploaded;
    }

    /**
     * Set upload_id
     *
     * @param string $uploadId
     * @return Uploads
     */
    public function setUploadId($uploadId)
    {
        $this->upload_id = $uploadId;

        return $this;
    }

    /**
     * Get upload_id
     *
     * @return string 
     */
    public function getUploadId()
    {
        return $this->upload_id;
    }

    /**
     * Set uploadKey
     *
     * @param string $uploadKey
     * @return Uploads
     */
    public function setUploadKey($uploadKey)
    {
        $this->uploadKey = $uploadKey;

        return $this;
    }

    /**
     * Get uploadKey
     *
     * @return string 
     */
    public function getUploadKey()
    {
        return $this->uploadKey;
    }

    /**
     * Set upload_start
     *
     * @param \DateTime $uploadStart
     * @return Uploads
     */
    public function setUploadStart($uploadStart)
    {
        $this->upload_start = $uploadStart;

        return $this;
    }

    /**
     * Get upload_start
     *
     * @return \DateTime 
     */
    public function getUploadStart()
    {
        return $this->upload_start;
    }

    /**
     * Set last_information
     *
     * @param \DateTime $lastInformation
     * @return Uploads
     */
    public function setLastInformation($lastInformation)
    {
        $this->last_information = $lastInformation;

        return $this;
    }

    /**
     * Get last_information
     *
     * @return \DateTime 
     */
    public function getLastInformation()
    {
        return $this->last_information;
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
