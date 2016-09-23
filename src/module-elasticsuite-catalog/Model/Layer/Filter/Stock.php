<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * This stock filter is a fork of Marius Strajeru ( http://marius-strajeru.blogspot.fr/ ) previous Module
 * available at https://github.com/tzyganu/magento2-stock-filter/
 *
 * @category  Smile
 * @package   Smile\ElasticSuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Layer\Filter;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Stock filter model implementation
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 *
 * @category Smile
 * @package  Smile\ElasticSuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Stock extends Attribute
{
    /**
     * @var string
     */
    protected $_requestVar = 'is_in_stock';

    /**
     * Get filter text label
     *
     * @return string
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getName()
    {
        return __('Availability');
    }

    /**
     * Append the facet to the product collection.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\Layer\Filter\Attribute
     */
    public function addFacetToCollection()
    {
        $facetField  = $this->getFilterField();
        $facetType   = BucketInterface::TYPE_TERM;

        $productCollection = $this->getLayer()->getProductCollection();
        $productCollection->addFacet($facetField, $facetType, []);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        $attributeValue = $request->getParam($this->_requestVar);
        $statusLabels   = $this->getStatusLabels();
        if (!is_array($attributeValue)) {
            $attributeValue = [$attributeValue];
        }

        if (count(array_intersect($attributeValue, array_keys($statusLabels))) > 0) {
            $this->currentFilterValue = $attributeValue;

            /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
            $productCollection = $this->getLayer()->getProductCollection();
            $productCollection->addFieldToFilter($this->getFilterField(), $attributeValue);
            $stockFiltersLabels = [];
            foreach ($this->currentFilterValue as $currentFilter) {
                if (isset($statusLabels[(int) $currentFilter])) {
                    $stockFiltersLabels[] = $statusLabels[(int) $currentFilter];
                }
            }

            $filterLabel = implode(', ', $stockFiltersLabels);
            $filter = $this->_createItem($filterLabel, $this->currentFilterValue);
            $this->getLayer()->getState()->addFilter($filter);
        }

        return $this;
    }

    /**
     * Retrieve ES filter field.
     *
     * @return string
     */
    protected function getFilterField()
    {
        $field = 'stock.is_in_stock';

        return $field;
    }

    /**
     * Get data array for building filter items
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return array
     */
    protected function _getItemsData()
    {
        $items = [];

        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getProductCollection();

        $optionsFacetedData = $productCollection->getFacetedData($this->getFilterField());
        $statusLabels = $this->getStatusLabels();

        foreach ($optionsFacetedData as $value => $data) {
            if (isset($statusLabels[(int) $value])) {
                $label = (string) $statusLabels[(int) $value];
                $items[$label] = [
                    'label' => $label,
                    'value' => (int) $value,
                    'count' => $data['count'],
                ];
            }
        }

        return $items;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * {@inheritDoc}
     */
    protected function _initItems()
    {
        parent::_initItems();

        foreach ($this->_items as $item) {
            $applyValue = $item->getValue();
            if (($valuePos = array_search($applyValue, $this->currentFilterValue)) !== false) {
                $item->setIsSelected(true);
                $applyValue = $this->currentFilterValue;
                unset($applyValue[$valuePos]);
            } else {
                $applyValue = array_merge($this->currentFilterValue, [$applyValue]);
            }

            $item->setApplyFilterValue($applyValue);
        }
    }

    /**
     * Retrieve Stock status labels
     *
     * @return array
     */
    private function getStatusLabels()
    {
        $statusLabels = [
            \Magento\CatalogInventory\Model\Stock::STOCK_IN_STOCK => __('In Stock'),
            \Magento\CatalogInventory\Model\Stock::STOCK_OUT_OF_STOCK => __('Out Of Stock'),
        ];

        return $statusLabels;
    }
}
