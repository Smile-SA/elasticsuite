<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Catalog\Category;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category\DataProvider as CategoryDataProvider;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute\CollectionFactory as AttributeCollectionFactory;
use Smile\ElasticsuiteCatalog\Model\Category\FilterableAttribute\Source\DisplayMode;

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
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    private $attributes = null;

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

        $data[$currentCategory->getId()]['layered_navigation_filters'] = $this->getFilterableAttributeList($currentCategory);

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
        $configuration = [];

        foreach ($this->getAttributes($currentCategory) as $attribute) {
            $configuration[] = [
                'attribute_id'    => $attribute->getAttributeId(),
                'attribute_label' => $attribute->getFrontendLabel(),
                'position'        => $attribute->getPosition() ? $attribute->getPosition() : PHP_INT_MAX,
                'display_mode'    => $attribute->hasDisplayMode() ? $attribute->getDisplayMode() : DisplayMode::AUTO_DISPLAYED,
            ];
        }

        return $configuration;
    }

    /**
     * Retrieve attribute collection pre-filtered with only attribute filterable.
     *
     * @param CategoryInterface $category Category
     *
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface[]
     */
    private function getAttributes(CategoryInterface $category)
    {
        $extensionAttributes = $category->getExtensionAttributes();
        if (null !== $extensionAttributes && $category->getId()) {
            $this->attributes = $extensionAttributes->getFilterableAttributeList();
        }

        if ($this->attributes === null) {
            $collection = $this->attributeCollectionFactory->create(['category' => $category]);
            $collection
                ->setItemObjectClass(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
                ->addIsFilterableFilter()
                ->addStoreLabel($category->getStoreId())
                ->setOrder('position', 'ASC');

            $this->attributes = $collection->getItems();
        }

        return $this->attributes;
    }
}
