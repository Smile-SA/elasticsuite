<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration\Aggregation;

use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfiguration\AggregationResolverInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Aggregation Resolver for Container Configuration
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
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
        return $containerConfig->getAggregations($query, $filters, $queryFilters);
    }
}
