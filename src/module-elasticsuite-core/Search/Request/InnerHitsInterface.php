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

use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;

/**
 * Interface for inner hits (collapse or nested queries)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
interface InnerHitsInterface
{
    /**
     * Return the name of the inner hits section.
     *
     * @return string
     */
    public function getName();

    /**
     * Return the offset from which to retrieve the inner hits.
     *
     * @return int
     */
    public function getFrom();

    /**
     * Return the number of inner hits to retrieve.
     *
     * @return int
     */
    public function getSize();

    /**
     * Return the sort configuration to apply to the inner hits.
     *
     * @return SortOrderInterface[]
     */
    public function getSort();
}
