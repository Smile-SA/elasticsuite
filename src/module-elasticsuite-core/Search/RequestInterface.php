<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search;

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
     * Searched doucument type.
     *
     * @return string
     */
    public function getType();

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
}
