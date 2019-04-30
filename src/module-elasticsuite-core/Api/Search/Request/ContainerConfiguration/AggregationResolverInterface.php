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
namespace Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfiguration;

use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Aggregation Resolver Interface for search containers.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface AggregationResolverInterface
{
    /**
     * Returns aggregations configured in the search container, and according to currently applied query and filters.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request configuration.
     * @param string|QueryInterface           $query           Search request query.
     * @param array                           $filters         Search request filters.
     * @param QueryInterface[]                $queryFilters    Search request filters prebuilt as QueryInterface.
     *
     * @return array
     */
    public function getContainerAggregations(
        ContainerConfigurationInterface $containerConfig,
        $query = null,
        $filters = [],
        $queryFilters = []
    );
}
