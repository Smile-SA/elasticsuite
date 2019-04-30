<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Block\Adminhtml\Catalog\Category\Tab;

/**
 * Custom Category/Product Grid
 *
 * Overridden to remove "position" column
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Product extends \Magento\Catalog\Block\Adminhtml\Category\Tab\Product
{
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName) This Method is inherited
     */
    public function _prepareColumns()
    {
        parent::_prepareColumns();

        if ($this->getColumn('position')) {
            $this->removeColumn('position');
        }

        return $this;
    }
}
