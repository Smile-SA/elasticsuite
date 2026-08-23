<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Api\Product\Collection;

use Magento\Customer\Model\Group as CustomerGroup;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection;

/**
 * Provide the parameters to build an aggregation collecting price statistics for a given collection.
 * Used in the collection's overridden _prepareStatisticsData method.
 * Create your own implementation if your price logic differs from Elasticsuite/Magento native behavior.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 */
interface PriceStatsAggregationProviderInterface
{
    /**
     * Return an array of params to create an aggregation named $aggregationName
     * which is able to collect product price statistics
     * - count
     * - min price
     * - max price
     * - standard deviation
     *
     * @param Collection $collection      The collection to get the price statistics aggregation for.
     * @param string     $aggregationName The expected aggregation name.
     * @param int|null   $customerGroupId The current customer group, if defined.
     *
     * @return array
     */
    public function getAggregationData(
        Collection $collection,
        string $aggregationName,
        ?int $customerGroupId = CustomerGroup::NOT_LOGGED_IN_ID
    ): array;
}
