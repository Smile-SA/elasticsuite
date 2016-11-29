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
 * Category item filter override using URL rewrites.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Category extends \Magento\Catalog\Model\Layer\Filter\Item
{
    /**
     * {@inheritDoc}
     */
    public function getUrl()
    {
        $catRequestVar  = $this->getFilter()->getRequestVar();
        $pageRequestVar = $this->_htmlPagerBlock->getPageVarName();

        $queryParams = [
            $catRequestVar  => $this->getValue(),
            $pageRequestVar => null,
        ];

        foreach ($this->getFilter()->getLayer()->getState()->getFilters() as $currentFilterItem) {
            $currentRequestVar = $currentFilterItem->getFilter()->getRequestVar();

            if ($currentRequestVar != $catRequestVar) {
                $queryParams[$currentRequestVar] = null;
            }
        }

        $url = $this->_url->getUrl(
            '*/*/*',
            ['_current' => true, '_use_rewrite' => true, '_query' => $queryParams]
        );

        if ($this->getUrlRewrite()) {
            $url = $this->getUrlRewrite();
        }

        return $url;
    }
}
