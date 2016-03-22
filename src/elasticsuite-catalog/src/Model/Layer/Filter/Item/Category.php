<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCatalog\Model\Layer\Filter\Item;

/**
 * Category item filter override using URL rewrites.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Category extends \Magento\Catalog\Model\Layer\Filter\Item
{
    /**
     * {@inheritDoc}
     */
    public function getUrl()
    {
        $url = parent::getUrl();

        if ($this->getUrlRewrite()) {
            $url = $this->getUrlRewrite();
        }

        return $url;
    }
}
