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
 * Interface for the collapsing of search results.
 *
 * @package Smile\ElasticsuiteCore\Search\Request
 */
interface CollapseInterface
{
    /**
     * Return the field used to collapse search results.
     *
     * @return string
     */
    public function getField();

    /**
     * Return the inner hits configurations, if any.
     *
     * @return InnerHitsInterface[]
     */
    public function getInnerHits();
}
