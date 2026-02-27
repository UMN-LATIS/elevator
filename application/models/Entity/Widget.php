<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Widget
 */
#[ORM\Table(name: 'widgets')]
#[ORM\Index(name: 0, columns: ['directSearch'])]
#[ORM\Entity]
class Widget
{
    /**
     * @var int|null
     */
    #[ORM\Column(name: 'template_order', type: 'integer', nullable: true)]
    private $template_order;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'view_order', type: 'integer', nullable: true)]
    private $view_order;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'display', type: 'boolean', nullable: true)]
    private $display;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'displayInPreview', type: 'boolean', nullable: true)]
    private $displayInPreview;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'required', type: 'boolean', nullable: true)]
    private $required;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'searchable', type: 'boolean', nullable: true)]
    private $searchable;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'allow_multiple', type: 'boolean', nullable: true)]
    private $allow_multiple;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'attempt_autocomplete', type: 'boolean', nullable: true)]
    private $attempt_autocomplete;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'field_title', type: 'string', nullable: true)]
    private $field_title;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'label', type: 'string', nullable: true)]
    private $label;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'tooltip', type: 'string', nullable: true)]
    private $tooltip;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'field_data', type: 'json', nullable: true, options: ['jsonb' => true])]
    private $field_data;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'createdAt', type: 'datetime', nullable: true)]
    private $createdAt;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'modifiedAt', type: 'datetime', nullable: true)]
    private $modifiedAt;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'directSearch', type: 'boolean', nullable: true)]
    private $directSearch;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'clickToSearch', type: 'boolean', nullable: true)]
    private $clickToSearch;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'clickToSearchType', type: 'integer', nullable: true)]
    private $clickToSearchType;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var \Entity\Field_type
     */
    #[ORM\JoinColumn(name: 'field_type_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\Field_type::class)]
    private $field_type;

    /**
     * @var \Entity\Template
     */
    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Entity\Template::class)]
    private $template;



    /**
     * Set templateOrder.
     *
     * @param int|null $templateOrder
     *
     * @return Widget
     */
    public function setTemplateOrder($templateOrder = null)
    {
        $this->template_order = $templateOrder;

        return $this;
    }

    /**
     * Get templateOrder.
     *
     * @return int|null
     */
    public function getTemplateOrder()
    {
        return $this->template_order;
    }

    /**
     * Set viewOrder.
     *
     * @param int|null $viewOrder
     *
     * @return Widget
     */
    public function setViewOrder($viewOrder = null)
    {
        $this->view_order = $viewOrder;

        return $this;
    }

    /**
     * Get viewOrder.
     *
     * @return int|null
     */
    public function getViewOrder()
    {
        return $this->view_order;
    }

    /**
     * Set display.
     *
     * @param bool|null $display
     *
     * @return Widget
     */
    public function setDisplay($display = null)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get display.
     *
     * @return bool|null
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set displayInPreview.
     *
     * @param bool|null $displayInPreview
     *
     * @return Widget
     */
    public function setDisplayInPreview($displayInPreview = null)
    {
        $this->displayInPreview = $displayInPreview;

        return $this;
    }

    /**
     * Get displayInPreview.
     *
     * @return bool|null
     */
    public function getDisplayInPreview()
    {
        return $this->displayInPreview;
    }

    /**
     * Set required.
     *
     * @param bool|null $required
     *
     * @return Widget
     */
    public function setRequired($required = null)
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Get required.
     *
     * @return bool|null
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * Set searchable.
     *
     * @param bool|null $searchable
     *
     * @return Widget
     */
    public function setSearchable($searchable = null)
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Get searchable.
     *
     * @return bool|null
     */
    public function getSearchable()
    {
        return $this->searchable;
    }

    /**
     * Set allowMultiple.
     *
     * @param bool|null $allowMultiple
     *
     * @return Widget
     */
    public function setAllowMultiple($allowMultiple = null)
    {
        $this->allow_multiple = $allowMultiple;

        return $this;
    }

    /**
     * Get allowMultiple.
     *
     * @return bool|null
     */
    public function getAllowMultiple()
    {
        return $this->allow_multiple;
    }

    /**
     * Set attemptAutocomplete.
     *
     * @param bool|null $attemptAutocomplete
     *
     * @return Widget
     */
    public function setAttemptAutocomplete($attemptAutocomplete = null)
    {
        $this->attempt_autocomplete = $attemptAutocomplete;

        return $this;
    }

    /**
     * Get attemptAutocomplete.
     *
     * @return bool|null
     */
    public function getAttemptAutocomplete()
    {
        return $this->attempt_autocomplete;
    }

    /**
     * Set fieldTitle.
     *
     * @param string|null $fieldTitle
     *
     * @return Widget
     */
    public function setFieldTitle($fieldTitle = null)
    {
        $this->field_title = $fieldTitle;

        return $this;
    }

    /**
     * Get fieldTitle.
     *
     * @return string|null
     */
    public function getFieldTitle()
    {
        return $this->field_title;
    }

    /**
     * Set label.
     *
     * @param string|null $label
     *
     * @return Widget
     */
    public function setLabel($label = null)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label.
     *
     * @return string|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set tooltip.
     *
     * @param string|null $tooltip
     *
     * @return Widget
     */
    public function setTooltip($tooltip = null)
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    /**
     * Get tooltip.
     *
     * @return string|null
     */
    public function getTooltip()
    {
        return $this->tooltip;
    }

    /**
     * Set fieldData.
     *
     * @param array|null $fieldData
     *
     * @return Widget
     */
    public function setFieldData($fieldData = null)
    {
        $this->field_data = $fieldData;

        return $this;
    }

    /**
     * Get fieldData.
     *
     * @return array|null
     */
    public function getFieldData()
    {
        return $this->field_data;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime|null $createdAt
     *
     * @return Widget
     */
    public function setCreatedAt($createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set modifiedAt.
     *
     * @param \DateTime|null $modifiedAt
     *
     * @return Widget
     */
    public function setModifiedAt($modifiedAt = null)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * Get modifiedAt.
     *
     * @return \DateTime|null
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * Set directSearch.
     *
     * @param bool|null $directSearch
     *
     * @return Widget
     */
    public function setDirectSearch($directSearch = null)
    {
        $this->directSearch = $directSearch;

        return $this;
    }

    /**
     * Get directSearch.
     *
     * @return bool|null
     */
    public function getDirectSearch()
    {
        return $this->directSearch;
    }

    /**
     * Set clickToSearch.
     *
     * @param bool|null $clickToSearch
     *
     * @return Widget
     */
    public function setClickToSearch($clickToSearch = null)
    {
        $this->clickToSearch = $clickToSearch;

        return $this;
    }

    /**
     * Get clickToSearch.
     *
     * @return bool|null
     */
    public function getClickToSearch()
    {
        return $this->clickToSearch;
    }

    /**
     * Set clickToSearchType.
     *
     * @param int|null $clickToSearchType
     *
     * @return Widget
     */
    public function setClickToSearchType($clickToSearchType = null)
    {
        $this->clickToSearchType = $clickToSearchType;

        return $this;
    }

    /**
     * Get clickToSearchType.
     *
     * @return int|null
     */
    public function getClickToSearchType()
    {
        return $this->clickToSearchType;
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

    /**
     * Set fieldType.
     *
     * @param \Entity\Field_type|null $fieldType
     *
     * @return Widget
     */
    public function setFieldType(?\Entity\Field_type $fieldType = null)
    {
        $this->field_type = $fieldType;

        return $this;
    }

    /**
     * Get fieldType.
     *
     * @return \Entity\Field_type|null
     */
    public function getFieldType()
    {
        return $this->field_type;
    }

    /**
     * Set template.
     *
     * @param \Entity\Template|null $template
     *
     * @return Widget
     */
    public function setTemplate(?\Entity\Template $template = null)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template.
     *
     * @return \Entity\Template|null
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
