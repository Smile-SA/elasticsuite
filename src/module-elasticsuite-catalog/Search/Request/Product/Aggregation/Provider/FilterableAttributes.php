<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider;

use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\AttributeListInterface;
use Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\ModifierInterface;
use Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\AggregationResolver as ProductAttributesAggregationResolver;
use Smile\ElasticsuiteCore\Search\Request\ContainerConfigurationFactory;

/**
 * Default Aggregations Provider for product Requests.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class FilterableAttributes implements \Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfiguration\AggregationProviderInterface
{
    /**
     * @var AttributeListInterface
     */
    private $attributeList;

    /**
     * @var \Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\AggregationResolver
     */
    private $aggregationResolver;

    /**
     * @var string
     */
    private $requestName;

    /**
     * @var \Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\ModifierInterface[]
     */
    private $modifiersPool;

    /**
     * @param AttributeListInterface               $attributeList       Attributes List.
     * @param ProductAttributesAggregationResolver $aggregationResolver Product Attributes Aggregation Resolver.
     * @param string                               $requestName         Container Configuration name.
     * @param ModifierInterface[]                  $modifiersPool       Product Attributes modifiers.
     */
    public function __construct(
        AttributeListInterface $attributeList,
        ProductAttributesAggregationResolver $aggregationResolver,
        string $requestName,
        array $modifiersPool = []
    ) {
        $this->attributeList          = $attributeList;
        $this->aggregationResolver    = $aggregationResolver;
        $this->requestName            = $requestName;
        $this->modifiersPool          = $modifiersPool;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregations(
        $storeId,
        $query = null,
        $filters = [],
        $queryFilters = []
    ) {
        $attributes = $this->attributeList->getList();

        foreach ($this->modifiersPool as $modifier) {
            $attributes = $modifier->modifyAttributes($storeId, $this->requestName, $attributes, $query, $filters, $queryFilters);
        }

        $aggregations = $this->getAggregationsConfig($attributes);

        foreach ($this->modifiersPool as $modifier) {
            $aggregations = $modifier->modifyAggregations($storeId, $this->requestName, $aggregations, $query, $filters, $queryFilters);
        }

        return $aggregations;
    }

    /**
     * Get aggregations config.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $attributes The attributes
     *
     * @return array
     */
    private function getAggregationsConfig($attributes)
    {
        $aggregations = [];
        foreach ($attributes as $attribute) {
            $bucketConfig                        = $this->getBucketConfig($attribute);
            $aggregations[$bucketConfig['name']] = $bucketConfig;
        }

        return $aggregations;
    }

    /**
     * Get Bucket config for a given product attribute.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute Attribute
     *
     * @return array
     */
    private function getBucketConfig($attribute)
    {
        return $this->aggregationResolver->getAggregationData($attribute);
    }
}
