<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogGraphQl
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogGraphQl\Model\Resolver;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\CatalogGraphQl\DataProvider\Product\LayeredNavigation\LayerBuilder;
use Magento\Directory\Model\PriceCurrency;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Api\Data\StoreInterface;
use Smile\ElasticsuiteCatalogGraphQl\Model\Resolver\Layer\FiltersProvider;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;

/**
 * Elasticsuite custom implementation of GraphQL Aggregations Resolver
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Aggregations implements ResolverInterface
{
    /**
     * @var FiltersProvider
     */
    private $filtersProvider;

    /**
     * @var LayerBuilder
     */
    private $layerBuilder;

    /**
     * @var \Magento\Catalog\Model\Layer\Resolver
     */
    private $layerResolver;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Search\ContextInterface
     */
    private $searchContext;

    /**
     * @param FiltersProvider  $filtersProvider Filters Provider
     * @param LayerBuilder     $layerBuilder    Layer Builder
     * @param Resolver         $layerResolver   Layer Resolver
     * @param ContextInterface $searchContext   Search Context
     */
    public function __construct(
        FiltersProvider $filtersProvider,
        LayerBuilder $layerBuilder,
        Resolver $layerResolver,
        ContextInterface $searchContext
    ) {
        $this->filtersProvider = $filtersProvider;
        $this->layerBuilder    = $layerBuilder;
        $this->layerResolver   = $layerResolver;
        $this->searchContext   = $searchContext;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        if (!isset($value['layer_type']) || !isset($value['search_result'])) {
            return null;
        }

        $layerType    = $value['layer_type'] ?? Resolver::CATALOG_LAYER_CATEGORY;
        $aggregations = $value['search_result']->getSearchAggregation();

        if ($aggregations) {
            /** @var StoreInterface $store */
            $store   = $context->getExtensionAttributes()->getStore();
            $storeId = (int) $store->getId();
            $results = $this->layerBuilder->build($aggregations, $storeId);
            $results = $this->sortFilters($layerType, $results);

            return $results;
        }

        return [];
    }

    /**
     * In a context of a category navigation, sort the filters according to filter orders defined for this category.
     *
     * @param string $layerType Layer Type
     * @param array  $filters   Aggregations computed previously
     *
     * @return array
     */
    private function sortFilters($layerType, $filters)
    {
        if ($layerType === Resolver::CATALOG_LAYER_CATEGORY) {
            if ($this->searchContext->getCurrentCategory() && $this->searchContext->getCurrentCategory()->getId()) {
                try {
                    // Filters Provider will fetch the current category from the layer.
                    $this->layerResolver->get()->setCurrentCategory($this->searchContext->getCurrentCategory());
                } catch (\Magento\Framework\Exception\LocalizedException $exception) {
                    return $filters;
                }
            }
        }

        // Filters will be sorted according to current category.
        $categoryFilters = $this->filtersProvider->getFilters($layerType);
        /** @var \Magento\Catalog\Model\Layer\Filter\AbstractFilter $filter */

        $order = ['category_id', 'category_uid']; // The category filter is always displayed first in legacy frontend.
        foreach ($categoryFilters as $filter) {
            if (!$filter->hasAttributeModel()) {
                continue;
            }
            $order[] = $filter->getAttributeModel()->getAttributeCode();
        }

        // Sort the filters according to order computed for this category.
        $filters = array_replace(array_flip($order), $filters);

        // Some filters can be empty at this case : they are in $order, but not in aggregations (product list might have been filtered).
        $filters = array_filter(
            $filters,
            function ($filter) {
                return isset($filter['attribute_code']) && !empty($filter['attribute_code']);
            }
        );

        return $filters;
    }
}
