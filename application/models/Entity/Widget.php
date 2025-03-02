<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Widget
 *
 * @ORM\Table(name="widgets", indexes={@ORM\Index(name="0", columns={"directSearch"})})
 * @ORM\Entity
 */
class Widget
{
    /**
     * @var int|null
     *
     * @ORM\Column(name="template_order", type="integer", nullable=true)
     */
    private $template_order;

    /**
     * @var int|null
     *
     * @ORM\Column(name="view_order", type="integer", nullable=true)
     */
    private $view_order;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="display", type="boolean", nullable=true)
     */
    private $display;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="displayInPreview", type="boolean", nullable=true)
     */
    private $displayInPreview;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="required", type="boolean", nullable=true)
     */
    private $required;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="searchable", type="boolean", nullable=true)
     */
    private $searchable;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="allow_multiple", type="boolean", nullable=true)
     */
    private $allow_multiple;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="attempt_autocomplete", type="boolean", nullable=true)
     */
    private $attempt_autocomplete;

    /**
     * @var string|null
     *
     * @ORM\Column(name="field_title", type="string", nullable=true)
     */
    private $field_title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="label", type="string", nullable=true)
     */
    private $label;

    /**
     * @var string|null
     *
     * @ORM\Column(name="tooltip", type="string", nullable=true)
     */
    private $tooltip;

    /**
     * @var array|null
     *
     * @ORM\Column(name="field_data", type="json_array", nullable=true)
     */
    private $field_data;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="modifiedAt", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="directSearch", type="boolean", nullable=true)
     */
    private $directSearch;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="clickToSearch", type="boolean", nullable=true)
     */
    private $clickToSearch;

    /**
     * @var int|null
     *
     * @ORM\Column(name="clickToSearchType", type="integer", nullable=true)
     */
    private $clickToSearchType;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="widgets_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Entity\Field_type
     *
     * @ORM\ManyToOne(targetEntity="Entity\Field_type")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="field_type_id", referencedColumnName="id")
     * })
     */
    private $field_type;

    /**
     * @var \Entity\Template
     *
     * @ORM\ManyToOne(targetEntity="Entity\Template")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="template_id", referencedColumnName="id")
     * })
     */
    private $template;


}
