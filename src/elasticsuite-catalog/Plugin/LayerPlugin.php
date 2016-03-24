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
namespace Smile\ElasticSuiteCatalog\Plugin;

/**
 * Replace is in stock native filter on layer.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class LayerPlugin extends \Magento\CatalogInventory\Model\Plugin\Layer
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * {@inheritDoc}
     */
    public function beforePrepareProductCollection(
        \Magento\Catalog\Model\Layer $subject,
        \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection
    ) {
        if ($this->_isEnabledShowOutOfStock() === false) {
            $collection->addIsInStockFilter();
        }
    }
}
