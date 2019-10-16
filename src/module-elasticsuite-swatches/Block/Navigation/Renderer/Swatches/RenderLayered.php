<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteSwatches
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteSwatches\Block\Navigation\Renderer\Swatches;

use Magento\Catalog\Model\Layer\Filter\Item as FilterItem;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\Option;

/**
 * Override Magento standard swatches renderer block.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteSwatches
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class RenderLayered extends \Magento\Swatches\Block\LayeredNavigation\RenderLayered
{
    /**
     * Override the native method to sort swatch options in the expected
     * order (as defined in the admin attribute parameters).
     *
     * @return array
     */
    public function getSwatchData(): array
    {
        if (false === $this->eavAttribute instanceof Attribute) {
            throw new \RuntimeException('Magento_Swatches: RenderLayered: Attribute has not been set.');
        }

        $attributeOptions = [];
        // Collect parameter labels in the expected order.
        $attributeOptionsSort = [];
        // Build an array whose keys are the attribute option label and not option id as in the native method.
        $sortingArr = [];
        foreach ($this->filter->getItems() as $item) {
            $sortingArr[] = $item['label'];
        }

        foreach ($this->eavAttribute->getOptions() as $option) {
            if ($currentOption = $this->getFilterOption($this->filter->getItems(), $option)) {
                /*
                 * Built the array with the attribute options in the expected orders with the attribute option id
                 * as keys, because it's a requirement for the getSwatchesByOptionsId helper method.
                 */
                $attributeOptions[$option->getLabel()] = array_merge($currentOption, ['id' => $option->getValue()]);
            } elseif ($this->isShowEmptyResults()) {
                $attributeOptions[$option->getLabel()] = array_merge($this->getUnusedOption($option), ['id' => $option->getValue()]);
            }
        }

        foreach (array_merge(array_flip($sortingArr), $attributeOptions) as $item) {
            $attributeOptionsSort[$item['id']] = $item;
        }

        $attributeOptionIds = array_keys($attributeOptionsSort);
        $swatches = $this->swatchHelper->getSwatchesByOptionsId($attributeOptionIds);

        $data = [
            'attribute_id' => $this->eavAttribute->getId(),
            'attribute_code' => $this->eavAttribute->getAttributeCode(),
            'attribute_label' => $this->eavAttribute->getStoreLabel(),
            'options' => $attributeOptionsSort,
            'swatches' => $swatches,
        ];

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFilterOption(array $filterItems, Option $swatchOption)
    {
        $resultOption = false;
        $filterItem = $this->getFilterItemByLabel($filterItems, $swatchOption->getLabel());
        if ($filterItem && $this->isOptionVisible($filterItem)) {
            $resultOption = $this->getOptionViewData($filterItem, $swatchOption);
        }

        return $resultOption;
    }

    /**
     * {@inheritDoc}
     */
    protected function getOptionViewData(FilterItem $filterItem, Option $swatchOption)
    {
        $customStyle = '';

        $linkToOption = $filterItem->getUrl();

        if ($this->isOptionDisabled($filterItem)) {
            $customStyle = 'disabled';
            $linkToOption = 'javascript:void();';
        }

        if ($filterItem->getIsSelected()) {
            $customStyle = 'selected';
        }

        return [
            'label'        => $swatchOption->getLabel(),
            'link'         => $linkToOption,
            'custom_style' => $customStyle,
        ];
    }


    /**
     * Fetch Filter item by it's label.
     *
     * @param FilterItem[] $filterItems All Filter items
     * @param string       $filterLabel Filter Label
     *
     * @return bool|FilterItem
     */
    protected function getFilterItemByLabel(array $filterItems, $filterLabel)
    {
        foreach ($filterItems as $item) {
            if ((string) $item->getValue() === (string) $filterLabel) {
                return $item;
            }
        }

        return false;
    }
}
