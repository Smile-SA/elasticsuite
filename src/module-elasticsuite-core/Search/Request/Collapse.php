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
 * Search request collapse implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class Collapse implements CollapseInterface
{
    /**
     * @var string
     */
    private $field;

    /**
     * @var InnerHitsInterface[]
     */
    private $innerHits;

    /**
     * Collapse constructor.
     *
     * @param string               $field     Field to collapse results on.
     * @param InnerHitsInterface[] $innerHits Inner hits.
     */
    public function __construct($field, $innerHits = [])
    {
        $this->field = $field;
        $this->innerHits = $innerHits;
    }

    /**
     * {@inheritDoc}
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * {@inheritDoc}
     */
    public function getInnerHits()
    {
        return $this->innerHits;
    }
}
