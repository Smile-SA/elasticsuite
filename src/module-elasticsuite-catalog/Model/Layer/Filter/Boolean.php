<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Layer\Filter;

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
     * {@inheritDoc}
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        $attributeValue = $request->getParam($this->_requestVar);

        if (!empty($attributeValue)) {
            if (!is_array($attributeValue)) {
                $attributeValue = [$attributeValue];
            }

            $this->currentFilterValue = $attributeValue;

            /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
            $productCollection = $this->getLayer()->getProductCollection();

            $productCollection->addFieldToFilter($this->getFilterField(), $attributeValue);
            $layerState = $this->getLayer()->getState();

            $booleanFilterLabels = [];
            foreach ($this->currentFilterValue as $currentFilter) {
                $booleanFilterLabels[] = (string) $this->getAttributeModel()->getSource()->getOptionText((int) $currentFilter);
            }
            $filterLabel = implode(', ', $booleanFilterLabels);

            $filter = $this->_createItem($filterLabel, $this->currentFilterValue);

            $layerState->addFilter($filter);
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
     * Retrieve ES filter field.
     *
     * @return string
     */
    protected function getFilterField()
    {
        $field = $this->getAttributeModel()->getAttributeCode();

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

        foreach ($this->_items as $item) {
            $applyValue = $item->getLabel();

            if ($item->getValue() == \Magento\Eav\Model\Entity\Attribute\Source\Boolean::VALUE_YES) {
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
