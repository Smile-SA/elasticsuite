<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Search\Request\Product\Coverage\Aggregation;

use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfiguration\AggregationResolverInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Coverage request aggregation resolver.
 * Returns only data we plan to build coverage on (attribute_set_id, indexed_attributes).
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Resolver implements AggregationResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function getContainerAggregations(
        ContainerConfigurationInterface $containerConfig,
        $query = null,
        $filters = [],
        $queryFilters = []
    ) {
        return [
            ['name' => 'attribute_set_id', 'type' => BucketInterface::TYPE_TERM, 'size' => 0],
            ['name' => 'indexed_attributes', 'type' => BucketInterface::TYPE_TERM, 'size' => 0],
        ];
    }
}
