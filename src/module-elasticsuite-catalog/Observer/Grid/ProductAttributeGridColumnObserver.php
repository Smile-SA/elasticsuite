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
 * This observer class observes the "product_attribute_grid_build" event.
 *
 * And adds two columns "Search Weight" and "Is filterable in search" to the product attribute grid.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class ProductAttributeGridColumnObserver implements ObserverInterface
{
    /**
     * Execute.
     *
     * @param Observer $observer Observer.
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Block\Adminhtml\Product\Attribute\Grid $grid */
        $grid = $observer->getGrid();

        // Add "Search Weight" column after "Searchable" column.
        $grid->addColumnAfter(
            'search_weight',
            [
                'header' => __('Search Weight'),
                'index' => 'search_weight',
                'type' => 'text',
                'align' => 'center',
                'sortable' => true,
                'escape' => true,
            ],
            'is_searchable'
        );

        // Add "Is filterable in search" column after "Use in layered navigation" column.
        $grid->addColumnAfter(
            'is_filterable_in_search',
            [
                'header' => __('Is Filterable in Search'),
                'index' => 'is_filterable_in_search',
                'type' => 'options',
                'options' => ['1' => __('Yes'), '0' => __('No')],
                'align' => 'center',
                'sortable' => true,
                'escape' => true,
            ],
            'is_filterable'
        );

        // Sort columns by predefined order.
        $grid->sortColumnsByOrder();
    }
}
