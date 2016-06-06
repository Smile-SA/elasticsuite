<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Layer\Filter\Item;

/**
 * Attribute item filter override.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Attribute extends \Magento\Catalog\Model\Layer\Filter\Item
{
    /**
     * {@inheritDoc}
     */
    public function getUrl()
    {
        $value = $this->getValue();

        if (is_array($this->getApplyFilterValue())) {
            $value = $this->getApplyFilterValue();
        }

        if (is_array($value) && count($value) == 1) {
            $value = current($value);
        }

        $query = [
            $this->getFilter()->getRequestVar() => $value,
            $this->_htmlPagerBlock->getPageVarName() => null,
        ];

        return $this->_url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);
    }
}
