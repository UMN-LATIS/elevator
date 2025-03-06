<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GroupEntry
 */
#[ORM\Table(name: 'group_entry')]
#[ORM\Entity]
class GroupEntry
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'groupValue', type: 'string', nullable: true)]
    private $groupValue;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;



    /**
     * Set groupValue.
     *
     * @param string|null $groupValue
     *
     * @return GroupEntry
     */
    public function setGroupValue($groupValue = null)
    {
        $this->groupValue = $groupValue;

        return $this;
    }

    /**
     * Get groupValue.
     *
     * @return string|null
     */
    public function getGroupValue()
    {
        return $this->groupValue;
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
