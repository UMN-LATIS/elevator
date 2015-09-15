<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GroupEntry
 */
class GroupEntry
{
    /**
     * @var integer
     */
    private $groupValue;

    /**
     * @var integer
     */
    private $id;


    /**
     * Set groupValue
     *
     * @param integer $groupValue
     * @return GroupEntry
     */
    public function setGroupValue($groupValue)
    {
        $this->groupValue = $groupValue;

        return $this;
    }

    /**
     * Get groupValue
     *
     * @return integer 
     */
    public function getGroupValue()
    {
        return $this->groupValue;
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
