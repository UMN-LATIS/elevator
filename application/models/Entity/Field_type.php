<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Field_type
 *
 * @ORM\Table(name="field_types")
 * @ORM\Entity
 */
class Field_type
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="model_name", type="string", nullable=true)
     */
    private $model_name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sample_field_data", type="text", nullable=true)
     */
    private $sample_field_data;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="field_types_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;



    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Field_type
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set modelName.
     *
     * @param string|null $modelName
     *
     * @return Field_type
     */
    public function setModelName($modelName = null)
    {
        $this->model_name = $modelName;

        return $this;
    }

    /**
     * Get modelName.
     *
     * @return string|null
     */
    public function getModelName()
    {
        return $this->model_name;
    }

    /**
     * Set sampleFieldData.
     *
     * @param string|null $sampleFieldData
     *
     * @return Field_type
     */
    public function setSampleFieldData($sampleFieldData = null)
    {
        $this->sample_field_data = $sampleFieldData;

        return $this;
    }

    /**
     * Get sampleFieldData.
     *
     * @return string|null
     */
    public function getSampleFieldData()
    {
        return $this->sample_field_data;
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
