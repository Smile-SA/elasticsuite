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

namespace Smile\ElasticsuiteCore\Search\Request;

/**
 * Extension of Magento default bucket interface :
 *
 * - Define new usable bucket types in ElasticSuite (histogrrams)
 * - Additional methods to handle nested and filtered aggregations
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface BucketInterface extends \Magento\Framework\Search\Request\BucketInterface
{
    const TYPE_HISTOGRAM        = 'histogramBucket';
    const TYPE_DATE_HISTOGRAM   = 'dateHistogramBucket';
    const TYPE_QUERY_GROUP      = 'queryGroupBucket';
    const TYPE_SIGNIFICANT_TERM = 'significantTermBucket';
    const TYPE_REVERSE_NESTED   = 'reverseNestedBucket';
    const TYPE_TOP_HITS         = 'topHitsBucket';
    const TYPE_METRIC           = 'metricBucket';

    const SORT_ORDER_COUNT     = '_count';
    const SORT_ORDER_TERM      = '_key';
    const SORT_ORDER_RELEVANCE = "_score";
    const SORT_ORDER_MANUAL    = "_manual";
    const SORT_ORDER_TERM_DEPRECATED = '_term';

    /**
     * @var integer
     */
    const MAX_BUCKET_SIZE = 10000;

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
     * @return BucketInterface[]
     */
    public function getChildBuckets();

    /**
     * Returns child pipeline aggregations
     *
     * @return PipelineInterface[]
     */
    public function getPipelines();
}
