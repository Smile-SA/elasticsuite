<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteSwatches
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
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
        $filterItem = $this->getFilterItemById($filterItems, $swatchOption->getLabel());
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
}
