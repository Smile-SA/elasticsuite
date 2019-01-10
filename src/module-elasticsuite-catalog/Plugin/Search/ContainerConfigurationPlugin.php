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
namespace Smile\ElasticsuiteCatalog\Plugin\Search;

use Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\AttributesProvider;
use Smile\ElasticsuiteCatalog\Search\Request\Product\AggregationProviderInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCatalog\Search\Request\Product\AggregationProviderInterfaceFactory;

/**
 * Plugin on Container Configuration to dynamically add Aggregations from product attributes.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ContainerConfigurationPlugin
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Search\Request\Product\AggregationProviderInterfaceFactory
     */
    private $aggregationsProviderFactory;

    /**
     * @var AggregationProviderInterface
     */
    private $aggregationProvider;

    /**
     * ContainerConfigurationPlugin constructor.
     *
     * @param AggregationProviderInterfaceFactory $aggregationsProviderFactory Attribute Provider
     */
    public function __construct(AggregationProviderInterfaceFactory $aggregationsProviderFactory)
    {
        $this->aggregationsProviderFactory = $aggregationsProviderFactory;
    }

    /**
     * Dynamically add aggregation to product related containers.
     *
     * @param \Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface $subject      Container
     * @param \Closure                                                                   $proceed      Parent function
     * @param null                                                                       $query        Current Query
     * @param array                                                                      $filters      Applied filters
     * @param array                                                                      $queryFilters Applied Query Filters
     *
     * @return array
     */
    public function aroundGetAggregations(
        ContainerConfigurationInterface $subject,
        \Closure $proceed,
        $query = null,
        $filters = [],
        $queryFilters = []
    ) {
        $result = $proceed($query, $filters, $queryFilters);

        if ($subject->getTypeName() === 'product') {
            $aggregationProvider = $this->getAggregationProvider();
            $aggregations        = $aggregationProvider->getAggregations($subject, $query, $filters, $queryFilters);
            $result              = array_merge($result, $aggregations);
        }

        return $result;
    }

    /**
     * Get Aggregation Provider, only once.
     *
     * @return AggregationProviderInterface
     */
    private function getAggregationProvider()
    {
        if (null === $this->aggregationProvider) {
            $this->aggregationProvider = $this->aggregationsProviderFactory->create();
        }

        return $this->aggregationProvider;
    }
}
