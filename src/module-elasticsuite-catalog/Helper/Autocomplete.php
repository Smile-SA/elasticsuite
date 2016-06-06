<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalog\Helper;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\Context;

/**
 * Autocomplete helper for Catalog Autocomplete
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Autocomplete extends \Smile\ElasticSuiteCore\Helper\Autocomplete
{
    /**
     * @var \Magento\CatalogInventory\Api\StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * Constructor.
     *
     * @param Context                     $context            Helper context.
     * @param StoreManagerInterface       $storeManager       Store manager.
     * @param StockConfigurationInterface $stockConfiguration Stock Configuration Interface.
     */
    public function __construct(Context $context, StoreManagerInterface $storeManager, StockConfigurationInterface $stockConfiguration)
    {
        $this->storeManager       = $storeManager;
        $this->stockConfiguration = $stockConfiguration;

        parent::__construct($context, $storeManager);
    }

    /**
     * Check if Stock configuration allows to display out of stock products
     *
     * @param int $storeId The store Id. Will use current store if null.
     *
     * @return bool
     */
    public function isShowOutOfStock($storeId = null)
    {
        return $this->stockConfiguration->isShowOutOfStock($storeId);
    }
}
