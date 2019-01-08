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

use Smile\ElasticsuiteCatalog\Model\Attribute\Source\FilterDisplayMode;
use Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\AggregationResolver as ProductAttributesAggregationResolver;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributesCollectionFactory;
use Smile\ElasticsuiteCatalog\Search\Request\Product\Coverage\ProviderFactory as CoverageProviderFactory;

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
     * @var \Smile\ElasticsuiteCore\Search\Request\Builder
     */
    private $coverageRequestBuilder;

    /**
     * @var \Smile\ElasticsuiteCatalog\Search\Request\Product\Coverage\ProviderFactory
     */
    private $coverageProviderFactory;

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
     * @param \Smile\ElasticsuiteCore\Search\Request\Builder $coverageRequestBuilder            Coverage Request Builder.
     * @param ProductAttributesCollectionFactory             $productAttributeCollectionFactory Product Attributes Collection Factory.
     * @param ProductAttributesAggregationResolver           $aggregationResolver               Product Attributes Aggregation Resolver.
     * @param CoverageProviderFactory                        $coverageProviderFactory           Coverage Provider Factory
     * @param array                                          $productContainers                 Product Containers.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Builder $coverageRequestBuilder,
        ProductAttributesCollectionFactory $productAttributeCollectionFactory,
        ProductAttributesAggregationResolver $aggregationResolver,
        CoverageProviderFactory $coverageProviderFactory,
        array $productContainers = []
    ) {
        $this->coverageRequestBuilder            = $coverageRequestBuilder;
        $this->coverageProviderFactory           = $coverageProviderFactory;
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
        $this->aggregationResolver               = $aggregationResolver;
        $this->productContainers                 = array_merge($this->defaultProductContainers, $productContainers);
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
     * @internal param array $result Aggregations
     */
    public function aroundGetAggregations(
        ContainerConfigurationInterface $subject,
        \Closure $proceed,
        $query = null,
        $filters = [],
        $queryFilters = []
    ) {
        $result = $proceed($query, $filters, $queryFilters);

        if (in_array($subject->getName(), array_keys($this->productContainers))) {

            $coverageRates = $this->getCoverageRates($subject, $query, $filters, $queryFilters);

            $aggregations = $this->getProductAttributesAggregations(
                $this->productContainers[$subject->getName()],
                $coverageRates
            );

            $result = array_merge($result, $aggregations);
        }

        return $result;
    }

    /**
     * Get coverage rate of attributes for current search request.
     *
     * @param \Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface $subject      Container
     * @param null                                                                       $query        Current Query
     * @param array                                                                      $filters      Applied filters
     * @param array                                                                      $queryFilters Applied Query Filters
     *
     * @return array
     */
    private function getCoverageRates(ContainerConfigurationInterface $subject, $query, $filters, $queryFilters)
    {
        $coverageRequest = $this->coverageRequestBuilder->create(
            $subject->getStoreId(),
            $subject->getName(),
            0,
            0,
            $query,
            [],
            $filters,
            $queryFilters
        );

        $coverage      = $this->coverageProviderFactory->create(['request' => $coverageRequest]);
        $coverageRates = [];
        $totalCount    = $coverage->getSize();

        foreach ($coverage->getProductCountByAttributeCode() as $attributeCode => $productCount) {
            $coverageRates[$attributeCode] = $productCount / $totalCount * 100;
        }

        return $coverageRates;
    }

    /**
     * Get product attributes aggregations.
     *
     * @param string $attributeType Condition value on attributes.
     * @param array $coverageRates  Current coverage rates of attributes for query.
     *
     * @return array
     */
    private function getProductAttributesAggregations($attributeType, $coverageRates)
    {
        $aggregations = [];
        $attributes   = $this->getFilterableAttributes();

        foreach ($attributes as $attribute) {
            if ($attribute->getData($attributeType) || ('category_ids' === $attribute->getAttributeCode())) {
                $bucketConfig                        = $this->getBucketConfig($attribute);
                $aggregations[$bucketConfig['name']] = $bucketConfig;

                try {
                    $attributeCode   = $attribute->getAttributeCode();
                    $minCoverageRate = $attribute->getFacetMinCoverageRate();

                    $isRelevant   = isset($coverageRates[$attributeCode]) && ($coverageRates[$attributeCode] >= $minCoverageRate);
                    $forceDisplay = $attribute->getFacetDisplayMode() == FilterDisplayMode::ALWAYS_DISPLAYED;
                    $isHidden     = $attribute->getFacetDisplayMode() == FilterDisplayMode::ALWAYS_HIDDEN;

                    if ($isHidden || !($isRelevant || $forceDisplay)) {
                        unset($aggregations[$bucketConfig['name']]);
                    }
                } catch (\Exception $e) {
                    ;
                }
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
