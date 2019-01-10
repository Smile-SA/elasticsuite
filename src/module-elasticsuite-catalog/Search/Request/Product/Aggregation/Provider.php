<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation;

use Smile\ElasticsuiteCatalog\Search\Request\Product\AggregationProviderInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Elasticsuite Product Aggregations provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Provider implements AggregationProviderInterface
{
    /**
     * @var AggregationProviderInterface[]
     */
    private $providersPool;

    /**
     * Provider constructor.
     *
     * @param AggregationProviderInterface[] $providersPool Providers Pool
     */
    public function __construct($providersPool = [])
    {
        $this->providersPool = $providersPool;
    }

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
    ) {
        $aggregations = [];

        foreach ($this->providersPool as $provider) {
            $aggregations = array_replace($aggregations, $provider->getAggregations($containerConfig, $query, $filters, $queryFilters));
        }

        return $aggregations;
    }
}
