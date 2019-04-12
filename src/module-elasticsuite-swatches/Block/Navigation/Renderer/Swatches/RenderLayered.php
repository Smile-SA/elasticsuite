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
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteSwatches\Block\Navigation\Renderer\Swatches;

use Magento\Catalog\Model\Layer\Filter\Item as FilterItem;
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
