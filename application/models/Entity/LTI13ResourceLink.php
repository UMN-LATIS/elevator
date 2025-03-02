<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LTI13ResourceLink
 *
 * @ORM\Table(name="lti13_resource_links")
 * @ORM\Entity
 */
class LTI13ResourceLink
{
    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="updatedAt", type="datetime", nullable=true, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $updatedAt = 'CURRENT_TIMESTAMP';

    /**
     * @var string|null
     *
     * @ORM\Column(name="resource_link", type="string", nullable=true)
     */
    private $resource_link;

    /**
     * @var string|null
     *
     * @ORM\Column(name="created_line_item", type="string", nullable=true)
     */
    private $created_line_item;

    /**
     * @var array|null
     *
     * @ORM\Column(name="endpoint", type="json_array", nullable=true)
     */
    private $endpoint;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="lti13_resource_links_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \Entity\LTI13Deployment
     *
     * @ORM\ManyToOne(targetEntity="Entity\LTI13Deployment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="deployment_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $deployment;


}
