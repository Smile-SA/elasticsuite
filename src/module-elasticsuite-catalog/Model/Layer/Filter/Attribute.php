<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Layer\Filter;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Product attribute filter implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Attribute extends \Magento\CatalogSearch\Model\Layer\Filter\Attribute
{
    /**
     * @var array
     */
    protected $currentFilterValue = [];

    /**
     * @var \Magento\Framework\Filter\StripTags
     */
    private $tagFilter;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var boolean
     */
    private $hasMoreItems = false;

    /**
     * @var \Smile\ElasticsuiteCatalog\Helper\ProductAttribute
     */
    private $mappingHelper;

    /**
     * Constructor.
     *
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory      $filterItemFactory Factory for item of the facets.
     * @param \Magento\Store\Model\StoreManagerInterface           $storeManager      Store manager.
     * @param \Magento\Catalog\Model\Layer                         $layer             Catalog product layer.
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder   Item data builder.
     * @param \Magento\Framework\Filter\StripTags                  $tagFilter         String HTML tags filter.
     * @param \Magento\Framework\Escaper                           $escaper           Html Escaper.
     * @param \Smile\ElasticsuiteCatalog\Helper\ProductAttribute   $mappingHelper     Mapping helper.
     * @param array                                                $data              Custom data.
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Filter\StripTags $tagFilter,
        \Magento\Framework\Escaper $escaper,
        \Smile\ElasticsuiteCatalog\Helper\ProductAttribute $mappingHelper,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $tagFilter,
            $data
        );

        $this->tagFilter     = $tagFilter;
        $this->escaper       = $escaper;
        $this->mappingHelper = $mappingHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        $attributeValue = $request->getParam($this->_requestVar);

        if (null !== $attributeValue) {
            if (!is_array($attributeValue)) {
                $attributeValue = [$attributeValue];
            }

            $this->currentFilterValue = array_values($attributeValue);

            /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
            $productCollection = $this->getLayer()->getProductCollection();

            $productCollection->addFieldToFilter($this->getFilterField(), $this->currentFilterValue);
            $layerState = $this->getLayer()->getState();

            foreach ($this->currentFilterValue as $currentFilter) {
                $filter = $this->_createItem($this->escaper->escapeHtml($currentFilter), $this->currentFilterValue);
                $layerState->addFilter($filter);
            }
        }

        return $this;
    }

    /**
     * Indicates if the facets has more documents to be displayed.
     *
     * @return boolean
     */
    public function hasMoreItems()
    {
        return $this->hasMoreItems;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * {@inheritDoc}
     */
    protected function _getItemsData()
    {
        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getProductCollection();
        $optionsFacetedData = $productCollection->getFacetedData($this->getFilterField());

        $items     = [];

        if (isset($optionsFacetedData['__other_docs'])) {
            $this->hasMoreItems = $optionsFacetedData['__other_docs']['count'] > 0;
            unset($optionsFacetedData['__other_docs']);
        }

        $minCount = !empty($optionsFacetedData) ? min(array_column($optionsFacetedData, 'count')) : 0;

        if (!empty($this->currentFilterValue) || $minCount < $productCollection->getSize()) {
            foreach ($optionsFacetedData as $value => $data) {
                $items[$value] = [
                    'label' => $this->tagFilter->filter($value),
                    'value' => $value,
                    'count' => $data['count'],
                ];
            }
        }

        $items = $this->addOptionsData($items);

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
            $applyValue = $item->getLabel();
            if (($valuePos = array_search($applyValue, $this->currentFilterValue)) !== false) {
                $item->setIsSelected(true);
                $applyValue = $this->currentFilterValue;
                unset($applyValue[$valuePos]);
            } else {
                $applyValue = array_merge($this->currentFilterValue, [$applyValue]);
            }

            $item->setApplyFilterValue(array_values($applyValue));
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
        return $this->mappingHelper->getFilterField($this->getAttributeModel());
    }

    /**
     * Resort items according option position defined in admin.
     *
     * @param array $items Items to be sorted.
     *
     * @return array
     */
    private function addOptionsData(array $items)
    {
        if ($this->getAttributeModel()->getFacetSortOrder() == BucketInterface::SORT_ORDER_MANUAL) {
            $options = $this->getAttributeModel()->getFrontend()->getSelectOptions();
            $optionPosition = 0;

            if (!empty($options)) {
                foreach ($options as $option) {
                    if (isset($option['label'])) {
                        // Manage labels that contains single quote.
                        $optionLabel = trim((string) htmlspecialchars($option['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false));
                        $optionPosition++;

                        if ($optionLabel !== null && isset($items[$optionLabel])) {
                            $items[$optionLabel]['adminSortIndex'] = $optionPosition;
                            $items[$optionLabel]['value']          = $option['value'];
                        }
                    }
                }
            }

            $items = $this->sortOptionsData($items);
        }

        return $items;
    }

    /**
     * Sort items by adminSortIndex key.
     *
     * @param array $items to be sorted.
     *
     * @return array
     */
    private function sortOptionsData(array $items)
    {

        usort($items, function ($item1, $item2) {
            if (!isset($item1['adminSortIndex']) or !isset($item2['adminSortIndex'])) {
                return 0;
            }

            return $item1['adminSortIndex'] <= $item2['adminSortIndex'] ? -1 : 1;
        });

        return $items;
    }
}
