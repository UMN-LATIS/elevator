<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Widget
 */
class Widget
{
    /**
     * @var integer
     */
    private $template_order;

    /**
     * @var integer
     */
    private $view_order;

    /**
     * @var boolean
     */
    private $display;

    /**
     * @var boolean
     */
    private $displayInPreview;

    /**
     * @var boolean
     */
    private $required;

    /**
     * @var boolean
     */
    private $searchable;

    /**
     * @var boolean
     */
    private $allow_multiple;

    /**
     * @var boolean
     */
    private $attempt_autocomplete;

    /**
     * @var string
     */
    private $field_title;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $tooltip;

    /**
     * @var array
     */
    private $field_data;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $modifiedAt;

    /**
     * @var boolean
     */
    private $directSearch;

    /**
     * @var boolean
     */
    private $clickToSearch;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Entity\Field_type
     */
    private $field_type;

    /**
     * @var \Entity\Template
     */
    private $template;


    /**
     * Set template_order
     *
     * @param integer $templateOrder
     * @return Widget
     */
    public function setTemplateOrder($templateOrder)
    {
        $this->template_order = $templateOrder;

        return $this;
    }

    /**
     * Get template_order
     *
     * @return integer
     */
    public function getTemplateOrder()
    {
        return $this->template_order;
    }

    /**
     * Set view_order
     *
     * @param integer $viewOrder
     * @return Widget
     */
    public function setViewOrder($viewOrder)
    {
        $this->view_order = $viewOrder;

        return $this;
    }

    /**
     * Get view_order
     *
     * @return integer
     */
    public function getViewOrder()
    {
        return $this->view_order;
    }

    /**
     * Set display
     *
     * @param boolean $display
     * @return Widget
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get display
     *
     * @return boolean
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set displayInPreview
     *
     * @param boolean $displayInPreview
     * @return Widget
     */
    public function setDisplayInPreview($displayInPreview)
    {
        $this->displayInPreview = $displayInPreview;

        return $this;
    }

    /**
     * Get displayInPreview
     *
     * @return boolean
     */
    public function getDisplayInPreview()
    {
        return $this->displayInPreview;
    }

    /**
     * Set required
     *
     * @param boolean $required
     * @return Widget
     */
    public function setRequired($required)
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Get required
     *
     * @return boolean
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * Set searchable
     *
     * @param boolean $searchable
     * @return Widget
     */
    public function setSearchable($searchable)
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Get searchable
     *
     * @return boolean
     */
    public function getSearchable()
    {
        return $this->searchable;
    }

    /**
     * Set allow_multiple
     *
     * @param boolean $allowMultiple
     * @return Widget
     */
    public function setAllowMultiple($allowMultiple)
    {
        $this->allow_multiple = $allowMultiple;

        return $this;
    }

    /**
     * Get allow_multiple
     *
     * @return boolean
     */
    public function getAllowMultiple()
    {
        return $this->allow_multiple;
    }

    /**
     * Set attempt_autocomplete
     *
     * @param boolean $attemptAutocomplete
     * @return Widget
     */
    public function setAttemptAutocomplete($attemptAutocomplete)
    {
        $this->attempt_autocomplete = $attemptAutocomplete;

        return $this;
    }

    /**
     * Get attempt_autocomplete
     *
     * @return boolean
     */
    public function getAttemptAutocomplete()
    {
        return $this->attempt_autocomplete;
    }

    /**
     * Set field_title
     *
     * @param string $fieldTitle
     * @return Widget
     */
    public function setFieldTitle($fieldTitle)
    {
        $this->field_title = $fieldTitle;

        return $this;
    }

    /**
     * Get field_title
     *
     * @return string
     */
    public function getFieldTitle()
    {
        return $this->field_title;
    }

    /**
     * Set label
     *
     * @param string $label
     * @return Widget
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set tooltip
     *
     * @param string $tooltip
     * @return Widget
     */
    public function setTooltip($tooltip)
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    /**
     * Get tooltip
     *
     * @return string
     */
    public function getTooltip()
    {
        return $this->tooltip;
    }

    /**
     * Set field_data
     *
     * @param array $fieldData
     * @return Widget
     */
    public function setFieldData($fieldData)
    {
        $this->field_data = $fieldData;

        return $this;
    }

    /**
     * Get field_data
     *
     * @return array
     */
    public function getFieldData()
    {
        return $this->field_data;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Widget
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set modifiedAt
     *
     * @param \DateTime $modifiedAt
     * @return Widget
     */
    public function setModifiedAt($modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * Get modifiedAt
     *
     * @return \DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * Set directSearch
     *
     * @param boolean $directSearch
     * @return Widget
     */
    public function setDirectSearch($directSearch)
    {
        $this->directSearch = $directSearch;

        return $this;
    }

    /**
     * Get directSearch
     *
     * @return boolean
     */
    public function getDirectSearch()
    {
        return $this->directSearch;
    }

    /**
     * Set clickToSearch
     *
     * @param boolean $clickToSearch
     * @return Widget
     */
    public function setClickToSearch($clickToSearch)
    {
        $this->clickToSearch = $clickToSearch;

        return $this;
    }

    /**
     * Get clickToSearch
     *
     * @return boolean
     */
    public function getClickToSearch()
    {
        return $this->clickToSearch;
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

    /**
     * Set field_type
     *
     * @param \Entity\Field_type $fieldType
     * @return Widget
     */
    public function setFieldType(\Entity\Field_type $fieldType = null)
    {
        $this->field_type = $fieldType;

        return $this;
    }

    /**
     * Get field_type
     *
     * @return \Entity\Field_type
     */
    public function getFieldType()
    {
        return $this->field_type;
    }

    /**
     * Set template
     *
     * @param \Entity\Template $template
     * @return Widget
     */
    public function setTemplate(\Entity\Template $template = null)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return \Entity\Template
     */
    public function getTemplate()
    {
        return $this->template;
    }
    /**
     * @var integer
     */
    private $clickToSearchType;


    /**
     * Set clickToSearchType
     *
     * @param integer $clickToSearchType
     *
     * @return Widget
     */
    public function setClickToSearchType($clickToSearchType)
    {
        $this->clickToSearchType = $clickToSearchType;

        return $this;
    }

    /**
     * Get clickToSearchType
     *
     * @return integer
     */
    public function getClickToSearchType()
    {
        return $this->clickToSearchType;
    }
}
