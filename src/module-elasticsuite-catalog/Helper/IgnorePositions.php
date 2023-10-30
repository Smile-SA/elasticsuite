<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteCatalog\Helper;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Ignore positions helper
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class IgnorePositions
{
    const XML_PATH_IGNORE_OOS_PRODUCT_POSITIONS = 'smile_elasticsuite_catalogsearch_settings/catalogsearch/ignore_oos_product_positions';

    /**
     * Scope configuration
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Stock configuration
     *
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var array
     */
    private $removeOosPositionsConfig = [];

    /**
     * Constructor
     *
     * @param ScopeConfigInterface        $scopeConfig        Scope configuration.
     * @param StockConfigurationInterface $stockConfiguration Stock configuration.
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StockConfigurationInterface $stockConfiguration
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * Returns true if categories and search queries positions should be ignored for Out of Stock products.
     *
     * @param integer $storeId Store id
     *
     * @return bool
     */
    public function isIgnoreOosPositions($storeId): bool
    {
        if (!array_key_exists($storeId, $this->removeOosPositionsConfig)) {
            $removeOosPositions = $this->scopeConfig->isSetFlag(
                self::XML_PATH_IGNORE_OOS_PRODUCT_POSITIONS,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $removeOosPositions = $removeOosPositions && $this->stockConfiguration->isShowOutOfStock($storeId);

            $this->removeOosPositionsConfig[$storeId] = $removeOosPositions;
        }

        return $this->removeOosPositionsConfig[$storeId];
    }
}
