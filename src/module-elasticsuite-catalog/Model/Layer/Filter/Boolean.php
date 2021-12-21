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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Layer\Filter;

use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Framework\Escaper;
use Magento\Framework\Filter\StripTags;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalog\Api\LayeredNavAttributeInterface;
use Smile\ElasticsuiteCatalog\Helper\ProductAttribute;
use Smile\ElasticsuiteCatalog\Model\Attribute\LayeredNavAttributesProvider;

/**
 * Product boolean filter implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Boolean extends Attribute
{
    /**
     * @var LayeredNavAttributesProvider
     */
    protected $layeredNavAttributesProvider;

    /**
     * @var array
     */
    protected $hideNoValueAttributes;

    /**
     * Boolean Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param ItemFactory                  $filterItemFactory            Factory for item of the facets.
     * @param StoreManagerInterface        $storeManager                 Store manager.
     * @param Layer                        $layer                        Catalog product layer.
     * @param DataBuilder                  $itemDataBuilder              Item data builder.
     * @param StripTags                    $tagFilter                    String HTML tags filter.
     * @param Escaper                      $escaper                      Html Escaper.
     * @param ProductAttribute             $mappingHelper                Mapping helper.
     * @param LayeredNavAttributesProvider $layeredNavAttributesProvider Layered navigation attributes Provider.
     * @param array                        $hideNoValueAttributes        Attributes for which we must hide the value no.
     * @param array                        $data                         Custom data.
     */
    public function __construct(
        ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        DataBuilder $itemDataBuilder,
        StripTags $tagFilter,
        Escaper $escaper,
        ProductAttribute $mappingHelper,
        LayeredNavAttributesProvider $layeredNavAttributesProvider,
        array $hideNoValueAttributes = [],
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $tagFilter,
            $escaper,
            $mappingHelper,
            $data
        );

        $this->layeredNavAttributesProvider = $layeredNavAttributesProvider;
        $this->hideNoValueAttributes = $hideNoValueAttributes;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        $attributeValue = $request->getParam($this->_requestVar);

        if ($attributeValue !== null) {
            if (!is_array($attributeValue)) {
                $attributeValue = [$attributeValue];
            }
            $this->currentFilterValue = $attributeValue;

            $attributeValue = array_map(function ($value) {
                return (bool) $value;
            }, $attributeValue);

            /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
            $productCollection = $this->getLayer()->getProductCollection();

            $productCollection->addFieldToFilter($this->getFilterField(), $attributeValue);
            $layerState = $this->getLayer()->getState();

            foreach ($this->currentFilterValue as $currentFilter) {
                $filter = $this->_createItem(
                    $this->getAttributeModel()->getSource()->getOptionText((int) $currentFilter),
                    $this->currentFilterValue
                );
                $filter->setRawValue($currentFilter);
                $layerState->addFilter($filter);
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function hasMoreItems()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFilterField()
    {
        $field = $this->getAttributeModel()->getAttributeCode();

        $layeredNavAttribute = $this->layeredNavAttributesProvider->getLayeredNavAttribute($field);
        if ($layeredNavAttribute instanceof LayeredNavAttributeInterface) {
            $field = $layeredNavAttribute->getFilterField();
        }

        return $field;
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

        foreach ($this->_items as $key => $item) {
            if ($item->getValue() == \Magento\Eav\Model\Entity\Attribute\Source\Boolean::VALUE_NO
                && in_array($this->getAttributeModel()->getAttributeCode(), $this->hideNoValueAttributes)
            ) {
                unset($this->_items[$key]);
                continue;
            }

            $applyValue = $item->getLabel();

            if ($item->getValue() == \Magento\Eav\Model\Entity\Attribute\Source\Boolean::VALUE_YES
                || $item->getValue() == \Magento\Eav\Model\Entity\Attribute\Source\Boolean::VALUE_NO
            ) {
                if (is_numeric($item->getLabel())) {
                    $label = $this->getAttributeModel()->getSource()->getOptionText((int) $item->getLabel());
                    $item->setLabel((string) $label);
                }
            }

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
}
