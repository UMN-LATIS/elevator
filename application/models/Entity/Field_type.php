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


}
