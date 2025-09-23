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
namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection;

use Magento\Customer\Model\Group as CustomerGroup;
use Smile\ElasticsuiteCatalog\Api\Product\Collection\PriceStatsAggregationProviderInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;

/**
 * Default product collection price statistics aggregation provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 */
class PriceStatsAggregationProvider implements PriceStatsAggregationProviderInterface
{
    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getAggregationData(
        Collection $collection,
        string $aggregationName,
        ?int $customerGroupId = CustomerGroup::NOT_LOGGED_IN_ID
    ): array {
        return [
            'name'       => $aggregationName,
            'type'       => BucketInterface::TYPE_METRIC,
            'field'      => 'price.price',
            'metricType' => MetricInterface::TYPE_STATS,
            'nestedFilter' => ['price.customer_group_id' => $customerGroupId],
        ];
    }
}
