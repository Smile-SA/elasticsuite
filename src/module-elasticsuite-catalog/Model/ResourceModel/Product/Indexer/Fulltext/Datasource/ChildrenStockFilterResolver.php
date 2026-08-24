<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Magento\Framework\DB\Select;
use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Children stock filter dispatching to the MSI or to the legacy implementation.
 *
 * Same runtime detection as the one used for the inventory datasource : MSI classes can not be
 * injected through the constructor since MSI modules can be removed from an installation.
 *
 * @see \Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource\InventoryData
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 */
class ChildrenStockFilterResolver implements ChildrenStockFilterInterface
{
    /**
     * @var ChildrenStockFilterInterface
     */
    private $filter;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager Object manager.
     * @param ConfigInterface        $config        Object manager configuration.
     */
    public function __construct(ObjectManagerInterface $objectManager, ConfigInterface $config)
    {
        $this->objectManager = $objectManager;
        $this->config        = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function addSalableFilter(Select $select, string $childAlias, int $storeId): Select
    {
        return $this->getFilter()->addSalableFilter($select, $childAlias, $storeId);
    }

    /**
     * Init proper filter implementation.
     *
     * Should be the MSI implementation if MSI modules are enabled, otherwise we fallback
     * to the old-style CatalogInventory implementation.
     *
     * @deprecated To be removed with the dismantlement of the legacy CatalogInventory module.
     *
     * @return ChildrenStockFilterInterface
     */
    private function getFilter(): ChildrenStockFilterInterface
    {
        if ($this->filter === null) {
            $filterName = ChildrenStockFilterMSI::class;

            try {
                // Will fail in case of missing MSI modules or dependencies.
                $stockResolver = $this->config->getPreference(
                    \Magento\InventorySalesApi\Api\StockResolverInterface::class
                );

                if (ltrim($stockResolver, '\\') === ltrim(\Magento\InventorySalesApi\Api\StockResolverInterface::class, '\\')) {
                    $filterName = ChildrenStockFilter::class;
                }
            } catch (\Exception $exception) {
                $filterName = ChildrenStockFilter::class;
            }

            $this->filter = $this->objectManager->get($filterName);
        }

        return $this->filter;
    }
}
