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
namespace Smile\ElasticsuiteCatalog\Search\Request\Product;

use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Product Aggregation Provider Interface
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface AggregationProviderInterface
{
    /**
     * Product content type.
     */
    const TYPE = 'product';

    /**
     * Get aggregations for a container and a current combination of query/filters/queryFilters.
     *
     * @param ContainerConfigurationInterface $containerConfig Container Configuration.
     * @param string|QueryInterface           $query           Search request query.
     * @param array                           $filters         Search request filters.
     * @param QueryInterface[]                $queryFilters    Search request filters prebuilt as QueryInterface.
     *
     * @return array
     */
    public function getAggregations(
        ContainerConfigurationInterface $containerConfig,
        $query = null,
        $filters = [],
        $queryFilters = []
    );
}
