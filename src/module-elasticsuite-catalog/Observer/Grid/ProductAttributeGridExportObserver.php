<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Observer\Grid;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * This observer class observes the "backend_block_widget_grid_prepare_grid_before" event.
 *
 * And adds a button for exporting product attributes to a CSV file on the product attribute grid.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class ProductAttributeGridExportObserver implements ObserverInterface
{
    /**
     * Execute.
     *
     * @param Observer $observer Observer.
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Block\Adminhtml\Product\Attribute\Grid $grid */
        $grid = $observer->getGrid();

        if ($grid instanceof \Magento\Catalog\Block\Adminhtml\Product\Attribute\Grid) {
            $grid->addExportType('*/*/exportProductAttributeCsv', __('CSV'));
        }
    }
}
