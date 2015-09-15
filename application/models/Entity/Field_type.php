<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Field_type
 */
class Field_type
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $model_name;

    /**
     * @var string
     */
    private $sample_field_data;

    /**
     * @var integer
     */
    private $id;


    /**
     * Set name
     *
     * @param string $name
     * @return Field_type
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set model_name
     *
     * @param string $modelName
     * @return Field_type
     */
    public function setModelName($modelName)
    {
        $this->model_name = $modelName;

        return $this;
    }

    /**
     * Get model_name
     *
     * @return string 
     */
    public function getModelName()
    {
        return $this->model_name;
    }

    /**
     * Set sample_field_data
     *
     * @param string $sampleFieldData
     * @return Field_type
     */
    public function setSampleFieldData($sampleFieldData)
    {
        $this->sample_field_data = $sampleFieldData;

        return $this;
    }

    /**
     * Get sample_field_data
     *
     * @return string 
     */
    public function getSampleFieldData()
    {
        return $this->sample_field_data;
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
