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

use Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\AggregationResolver as ProductAttributesAggregationResolver;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributesCollectionFactory;

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
        'catalog_view_container' => 'is_filterable',
        'quick_search_container' => 'is_filterable_in_search',
    ];

    /**
     * @var array
     */
    private $productContainers = [];

    /**
     * @param ProductAttributesCollectionFactory   $productAttributeCollectionFactory Product Attributes Collection Factory.
     * @param ProductAttributesAggregationResolver $aggregationResolver               Product Attributes Aggregation Resolver.
     * @param array                                $productContainers                 Product Containers.
     */
    public function __construct(
        ProductAttributesCollectionFactory $productAttributeCollectionFactory,
        ProductAttributesAggregationResolver $aggregationResolver,
        array $productContainers = []
    ) {
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
        $this->aggregationResolver               = $aggregationResolver;
        $this->productContainers                 = array_merge($this->defaultProductContainers, $productContainers);
    }

    /**
     * Dynamically add aggregation to product related containers.
     *
     * @param \Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface $subject Container
     * @param array                                                                      $result  Aggregations
     *
     * @return array
     */
    public function afterGetAggregations(ContainerConfigurationInterface $subject, $result)
    {
        if (in_array($subject->getName(), array_keys($this->productContainers))) {
            $result = array_merge($result, $this->getProductAttributesAggregations($this->productContainers[$subject->getName()]));
        }

        return $result;
    }

    /**
     * Get product attributes aggregations.
     *
     * @param string $attributeType Condition value on attributes.
     *
     * @return array
     */
    private function getProductAttributesAggregations($attributeType)
    {
        $aggregations = [];
        $attributes   = $this->getFilterableAttributes();

        foreach ($attributes as $attribute) {
            if ($attribute->getData($attributeType) || ('category_ids' === $attribute->getAttributeCode())) {
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

        return $productAttributes;
    }
}
