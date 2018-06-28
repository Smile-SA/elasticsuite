<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Ui\Category\Form;

use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteCatalog\Model\Attribute\Source\FilterDisplayMode;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\FilterableAttribute\Category\CollectionFactory as AttributeCollectionFactory;

use Magento\Catalog\Model\Category\DataProvider as CategoryDataProvider;

/**
 * Elasticsuite Data Provider Plugin for Category Edit Form.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class DataProviderPlugin
{
    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * DataProviderPlugin constructor.
     *
     * @param AttributeCollectionFactory $attributeCollectionFactory Attribute Collection Factory.
     */
    public function __construct(AttributeCollectionFactory $attributeCollectionFactory)
    {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    /**
     * Remove filter configuration from meta in case of a new category, or a root one.
     * Meta is added in the ui_component via XML.
     *
     * @param CategoryDataProvider $dataProvider Data provider.
     * @param \Closure             $proceed      Original method.
     *
     * @return array
     */
    public function aroundGetMeta(CategoryDataProvider $dataProvider, \Closure $proceed)
    {
        $meta = $proceed();

        $currentCategory = $dataProvider->getCurrentCategory();

        if ($currentCategory->getId() === null || $currentCategory->getLevel() < 2) {
            $meta['display_settings']['children']['facet_config']['arguments']['data']['config']['visible'] = false;
        }

        return $meta;
    }

    /**
     * Append filter configuration (sort order and display mode) data.
     * Meta is added in the ui_component via XML.
     *
     * @param CategoryDataProvider $dataProvider Data provider.
     * @param \Closure             $proceed      Original method.
     *
     * @return array
     */
    public function aroundGetData(CategoryDataProvider $dataProvider, \Closure $proceed)
    {
        $data = $proceed();

        $currentCategory = $dataProvider->getCurrentCategory();

        $data[$currentCategory->getId()]['facet_config'] = $this->getFilterableAttributeList($currentCategory);

        return $data;
    }

    /**
     * Retrieve facet configuration for current category.
     * Compute the intersection between existing data for the category, and all attributes set as filterable.
     *
     * @param CategoryInterface $currentCategory Current Category
     *
     * @return array
     */
    private function getFilterableAttributeList($currentCategory)
    {
        $attributesList = [];

        foreach ($this->getAttributes($currentCategory) as $attribute) {
            $attributesList[] = [
                'attribute_id'                        => (int) $attribute->getAttributeId(),
                'attribute_label'                     => $attribute->getFrontendLabel(),
                'facet_display_mode'                  => (int) ($attribute->getFacetDisplayMode() ?? FilterDisplayMode::AUTO_DISPLAYED),
                'facet_min_coverage_rate'             => $attribute->getFacetMinCoverageRate(),
                'use_default_facet_min_coverage_rate' => (bool) $attribute->getUseDefaultFacetMinCoverageRate(),
                'facet_max_size'                      => $attribute->getFacetMaxSize(),
                'use_default_facet_max_size'          => (bool) $attribute->getUseDefaultFacetMaxSize(),
                'facet_sort_order'                    => $attribute->getFacetSortOrder(),
                'use_default_facet_sort_order'        => (bool) $attribute->getUseDefaultFacetSortOrder(),
                'is_pinned'                           => !(bool) $attribute->getUseDefaultPosition(),
                'default_position'                    => (int) $attribute->getDefaultPosition(),
                'position'                            => (int) $attribute->getPosition(),
            ];
        }

        return $attributesList;
    }

    /**
     * Retrieve attribute collection pre-filtered with only attribute filterable.
     *
     * @param CategoryInterface $category Category
     *
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface[]
     */
    private function getAttributes($category)
    {
        $collection = $this->attributeCollectionFactory->create(['category' => $category]);
        $collection
            ->setCategory($category)
            ->addIsFilterableFilter()
            ->addStoreLabel($category->getStoreId())
            ->setOrder('position', 'ASC');

        return $collection->getItems();
    }
}
