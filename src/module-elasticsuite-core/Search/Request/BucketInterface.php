<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request;

/**
 * Extension of Magento default bucket interface :
 *
 * - Define new usable bucket types in ElasticSuite (histogrrams)
 * - Additional methods to handle nested and filtered aggregations
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface BucketInterface extends \Magento\Framework\Search\Request\BucketInterface
{
    const TYPE_HISTOGRAM       = 'histogramBucket';
    const TYPE_DATE_HISTOGRAM  = 'dateHistogramBucket';
    const TYPE_QUERY_GROUP     = 'queryGroupBucket';

    const SORT_ORDER_COUNT     = '_count';
    const SORT_ORDER_TERM      = '_term';
    const SORT_ORDER_RELEVANCE = "_score";
    const SORT_ORDER_MANUAL    = "_manual";

    /**
     * Indicates if the aggregation is nested.
     *
     * @return @boolean
     */
    public function isNested();

    /**
     * Nested path for nested aggregations.
     *
     * @return string
     */
    public function getNestedPath();

    /**
     * Optional filter for nested filters (eg. filter by customer group for price).
     *
     * @return QueryInterface|null
     */
    public function getNestedFilter();

    /**
     * Optional filter for filtered aggregations.
     *
     * @return QueryInterface|null
     */
    public function getFilter();

    /**
     * Returns child buckets.
     *
     * @return BuckerInterface[]
     */
    public function getChildBuckets();
}
