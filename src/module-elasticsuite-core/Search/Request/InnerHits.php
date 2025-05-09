<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request;

/**
 * Collapse section or nested queries inner hits implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class InnerHits implements InnerHitsInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $from;

    /**
     * @var integer
     */
    private $size;

    /**
     * @var SortOrderInterface[]
     */
    private $sort;

    /**
     * InnerHits constructor.
     *
     * @param string               $name Name of the inner hits section
     * @param int                  $from Inner hits retrieval offset
     * @param int                  $size Number of inner hits to retrieve
     * @param SortOrderInterface[] $sort Sort orders
     */
    public function __construct($name, $from = 0, $size = 3, $sort = [])
    {
        $this->name = $name;
        $this->from = $from;
        $this->size = $size;
        $this->sort = $sort;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * {@inheritDoc}
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * {@inheritDoc}
     */
    public function getSort()
    {
        return $this->sort;
    }
}
