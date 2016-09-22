<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Smile\ElasticsuiteCatalog\Model\Source\StockFilter\Position;

/**
 * Observer that handles addition of a stock filter.
 *
 * This stock filter is a fork of Marius Strajeru ( http://marius-strajeru.blogspot.fr/ ) previous Module
 * available at https://github.com/tzyganu/magento2-stock-filter/
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AddStockFilter implements ObserverInterface
{
    /**
     * Stock filter name
     */
    const STOCK_FILTER_NAME = 'stock';

    /**
     * The configuration path indicating if out of stock products are shown or not.
     */
    const SHOW_OUT_OF_STOCK_XML_PATH = 'cataloginventory/options/show_out_of_stock';

    /**
     * The configuration path indicating if stock filter displaying is enabled.
     */
    const CONFIG_STOCK_FILTER_ENABLED_XML_PATH = 'smile_elasticsuite_catalogsearch_settings/inventory_settings/stock_filter_enabled';

    /**
     * The configuration path indicating where the inventory filter should be displayed.
     */
    const CONFIG_STOCK_FILTER_POSITION_XML_PATH = 'smile_elasticsuite_catalogsearch_settings/inventory_settings/stock_filter_position';

    /**
     * @var \Magento\Framework\ObjectManagerInterface The object Manager
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface The Scope Configuration
     */
    private $scopeConfig;

    /**
     * AddStockFilter constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface          $objectManager The object Manager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig   The scope config
     */
    public function __construct(ObjectManagerInterface $objectManager, ScopeConfigInterface $scopeConfig)
    {
        $this->objectManager = $objectManager;
        $this->scopeConfig   = $scopeConfig;
    }

    /**
     * Append Stock filter to layer filters.
     *
     * @param Observer $observer The observer
     * @event smile_elasticsuite_layer_filterlist_get
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $layer       = $observer->getLayer();
        $filterTypes = $observer->getEventData()->getFilterTypes();
        $filters     = $observer->getEventData()->getFilters();

        if ($this->isShowOutOfStock() && $this->isEnabled() && isset($filterTypes[self::STOCK_FILTER_NAME])) {
            $filterClassName = $filterTypes[self::STOCK_FILTER_NAME];
            $addStockFilter  = true;
            foreach ($filters as $filterItem) {
                if ($filterItem instanceof $filterClassName) {
                    $addStockFilter = false;
                    break;
                }
            }

            if ($addStockFilter) {
                $this->addStockFilter($filters, $filterClassName, $layer);
                $observer->getEventData()->setFilters($filters);
            }
        }
    }

    /**
     * Add the filter to the current layer
     *
     * @param array                        $filters         The filters
     * @param string                       $filterClassName The stock filter class name
     * @param \Magento\Catalog\Model\Layer $layer           The layer
     */
    private function addStockFilter(&$filters, $filterClassName, $layer)
    {
        $position = $this->getFilterPosition();
        $filter = $this->objectManager->create($filterClassName, ['layer' => $layer]);

        if ($position === Position::POSITION_TOP) {
            array_unshift($filters, $filter);
        } elseif ($position === Position::POSITION_BOTTOM) {
            array_push($filters, $filter);
        } elseif ($position === Position::POSITION_AFTER_CATEGORY) {
            $offset = false;
            foreach ($filters as $key => $value) {
                if ($value instanceof \Smile\ElasticsuiteCatalog\Model\Layer\Filter\Category) {
                    $offset = $key + 1;
                }
            }
            if ($offset !== false) {
                array_splice($filters, (int) $offset, 0, [$filter]);
            }
        }
    }

    /**
     * Check if "Display products out of stock" is set to true in configuration.
     *
     * @return bool
     */
    private function isShowOutOfStock()
    {
        return $this->scopeConfig->isSetFlag(self::SHOW_OUT_OF_STOCK_XML_PATH, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if the stock filter display is enabled.
     *
     * @return bool
     */
    private function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_STOCK_FILTER_ENABLED_XML_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve the position to apply for the field.
     *
     * @return string
     */
    private function getFilterPosition()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_STOCK_FILTER_POSITION_XML_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
