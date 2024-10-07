<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search;

use Smile\ElasticsuiteCore\Search\Request\CollapseInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;

/**
 * ElasticSuite search requests interface.
 *
 * This extends the standard magento request interface to append support of the following features :
 * - document types
 * - hits filtering not applied to aggregations (ElasicSearch root filters)
 * - sort order definition
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface RequestInterface extends \Magento\Framework\Search\RequestInterface
{
    /**
     * Hits filter (does not apply to aggregations).

     * Filter are actually using QueryInterface since there is no differences
     * beetween queries and filters in Elasticsearch 2.x DSL.
     *
     * @return QueryInterface
     */
    public function getFilter();

    /**
     * Request sort order.
     *
     * @return SortOrderInterface[]
     */
    public function getSortOrders();

    /**
     * Indicates if the query has been spellchecked.
     *
     * @return boolean
     */
    public function isSpellchecked();

    /**
     * Get the request spelling type.
     *
     * @return string
     */
    public function getSpellingType();

    /**
     * Get the value of the track_total_hits parameter, if any.
     *
     * @return int|bool
     */
    public function getTrackTotalHits();

    /**
     * Get the value of the min_score parameter, if any.
     *
     * @return int|bool
     */
    public function getMinScore();

    /**
     * Set the collapse configuration of the request.
     *
     * @param CollapseInterface $collapse Collapse configuration.
     *
     * @return RequestInterface
     */
    public function setCollapse(CollapseInterface $collapse);

    /**
     * Return true if the request has a collapse configuration.
     *
     * @return bool
     */
    public function hasCollapse();

    /**
     * Get the collapse configuration of the request.
     *
     * @return CollapseInterface|null
     */
    public function getCollapse();
}
