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
namespace Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider;

use Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\ModifierInterface;
use Smile\ElasticsuiteCatalog\Search\Request\Product\AggregationProviderInterface;
use Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\AggregationResolver as ProductAttributesAggregationResolver;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributesCollectionFactory;
use Smile\ElasticsuiteCatalog\Search\Request\Product\Coverage\ProviderFactory as CoverageProviderFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Default Aggregations Provider for product Requests.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class FilterableAttributes implements AggregationProviderInterface
{
    /**
     * @var ProductAttributesCollectionFactory
     */
    private $productAttributeCollectionFactory;

    /**
     * @var \Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\AggregationResolver
     */
    private $aggregationResolver;

    /**
     * @var array
     */
    private $defaultProductContainers = [
        'catalog_view_container'       => 'is_filterable',
        'quick_search_container'       => 'is_filterable_in_search',
        'catalog_product_autocomplete' => 'is_displayed_in_autocomplete',
    ];

    /**
     * @var array
     */
    private $productContainers = [];

    /**
     * @var \Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\ModifierInterface[]
     */
    private $modifiersPool;

    /**
     * @param ProductAttributesCollectionFactory   $productAttributeCollectionFactory Product Attributes Collection Factory.
     * @param ProductAttributesAggregationResolver $aggregationResolver               Product Attributes Aggregation Resolver.
     * @param ModifierInterface[]                  $modifiersPool                     Product Attributes modifiers.
     * @param array                                $productContainers                 Default product containers.
     */
    public function __construct(
        ProductAttributesCollectionFactory $productAttributeCollectionFactory,
        ProductAttributesAggregationResolver $aggregationResolver,
        array $modifiersPool = [],
        array $productContainers = []
    ) {
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
        $this->aggregationResolver               = $aggregationResolver;
        $this->modifiersPool                     = $modifiersPool;
        $this->productContainers                 = array_merge($this->defaultProductContainers, $productContainers);
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

        if (in_array($containerConfig->getName(), array_keys($this->productContainers))) {
            $attributes = $this->getFilterableAttributes()->getItems();

            foreach ($this->modifiersPool as $modifier) {
                $attributes = $modifier->modifyAttributes($containerConfig, $attributes, $query, $filters, $queryFilters);
            }

            $aggregations = $this->getAggregationsConfig($this->productContainers[$containerConfig->getName()], $attributes);

            foreach ($this->modifiersPool as $modifier) {
                $aggregations = $modifier->modifyAggregations($containerConfig, $aggregations, $query, $filters, $queryFilters);
            }

        }

        return $aggregations;
    }

    /**
     * Get aggregations config.
     *
     * @param string                                                            $attributeCondition The attribute condition to test
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $attributes         The attributes
     *
     * @return array
     */
    private function getAggregationsConfig($attributeCondition, $attributes)
    {
        $aggregations = [];
        foreach ($attributes as $attribute) {
            if ($attribute->getData($attributeCondition) || ('category_ids' === $attribute->getAttributeCode())) {
                $bucketConfig                        = $this->getBucketConfig($attribute);
                $aggregations[$bucketConfig['name']] = $bucketConfig;
            }
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

    /**
     * Get a list of filterable product attributes.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    private function getFilterableAttributes()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $productAttributes */
        $productAttributes = $this->productAttributeCollectionFactory->create();
        $productAttributes->addFieldToFilter(
            ['is_filterable', 'is_filterable_in_search'],
            [[1, 2], 1]
        );

        $productAttributes->getSelect()->orWhere('attribute_code = "category_ids"');
        $productAttributes->setOrder('position', 'ASC');

        return $productAttributes;
    }
}
