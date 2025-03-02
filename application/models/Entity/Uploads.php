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



    /**
     * Set filename.
     *
     * @param string|null $filename
     *
     * @return Uploads
     */
    public function setFilename($filename = null)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename.
     *
     * @return string|null
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set filesize.
     *
     * @param string|null $filesize
     *
     * @return Uploads
     */
    public function setFilesize($filesize = null)
    {
        $this->filesize = $filesize;

        return $this;
    }

    /**
     * Get filesize.
     *
     * @return string|null
     */
    public function getFilesize()
    {
        return $this->filesize;
    }

    /**
     * Set lastModified.
     *
     * @param string|null $lastModified
     *
     * @return Uploads
     */
    public function setLastModified($lastModified = null)
    {
        $this->last_modified = $lastModified;

        return $this;
    }

    /**
     * Get lastModified.
     *
     * @return string|null
     */
    public function getLastModified()
    {
        return $this->last_modified;
    }

    /**
     * Set chunksUploaded.
     *
     * @param string|null $chunksUploaded
     *
     * @return Uploads
     */
    public function setChunksUploaded($chunksUploaded = null)
    {
        $this->chunks_uploaded = $chunksUploaded;

        return $this;
    }

    /**
     * Get chunksUploaded.
     *
     * @return string|null
     */
    public function getChunksUploaded()
    {
        return $this->chunks_uploaded;
    }

    /**
     * Set uploadId.
     *
     * @param string|null $uploadId
     *
     * @return Uploads
     */
    public function setUploadId($uploadId = null)
    {
        $this->upload_id = $uploadId;

        return $this;
    }

    /**
     * Get uploadId.
     *
     * @return string|null
     */
    public function getUploadId()
    {
        return $this->upload_id;
    }

    /**
     * Set uploadKey.
     *
     * @param string|null $uploadKey
     *
     * @return Uploads
     */
    public function setUploadKey($uploadKey = null)
    {
        $this->uploadKey = $uploadKey;

        return $this;
    }

    /**
     * Get uploadKey.
     *
     * @return string|null
     */
    public function getUploadKey()
    {
        return $this->uploadKey;
    }

    /**
     * Set uploadStart.
     *
     * @param \DateTime|null $uploadStart
     *
     * @return Uploads
     */
    public function setUploadStart($uploadStart = null)
    {
        $this->upload_start = $uploadStart;

        return $this;
    }

    /**
     * Get uploadStart.
     *
     * @return \DateTime|null
     */
    public function getUploadStart()
    {
        return $this->upload_start;
    }

    /**
     * Set lastInformation.
     *
     * @param \DateTime|null $lastInformation
     *
     * @return Uploads
     */
    public function setLastInformation($lastInformation = null)
    {
        $this->last_information = $lastInformation;

        return $this;
    }

    /**
     * Get lastInformation.
     *
     * @return \DateTime|null
     */
    public function getLastInformation()
    {
        return $this->last_information;
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
