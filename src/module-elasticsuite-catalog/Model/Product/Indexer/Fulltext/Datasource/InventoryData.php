<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\InventoryDataInterface;
use \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\Deprecation\InventoryData as Deprecation;

/**
 * Datasource used to append inventory data to product during indexing.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class InventoryData implements DatasourceInterface
{
    /**
     * @var InventoryDataInterface
     */
    private $resourceModel;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\ObjectManager\ConfigInterface
     */
    private $config;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface        $objectManager Object Manager
     * @param \Magento\Framework\ObjectManager\ConfigInterface $config        Configuration
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\ObjectManager\ConfigInterface $config
    ) {
        $this->objectManager = $objectManager;
        $this->config        = $config;
    }

    /**
     * Add inventory data to the index data.
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        $inventoryData = $this->getResourceModel()->loadInventoryData($storeId, array_keys($indexData));

        foreach ($inventoryData as $inventoryDataRow) {
            $productId = (int) $inventoryDataRow['product_id'];
            $indexData[$productId]['stock'] = [
                'is_in_stock' => (bool) $inventoryDataRow['stock_status'],
                'qty'         => (int) $inventoryDataRow['qty'],
            ];
        }

        return $indexData;
    }

    /**
     * Init proper resource model.
     *
     * Should be default implementation of InventoryDataInterface if MSI modules are enabled.
     *
     * Otherwise we fallback to old-style CatalogInventory indexing.
     *
     * @deprecated To be removed with Magento 2.4 and the dismantlement of legacy CatalogInventory module.
     *
     * @return InventoryDataInterface
     */
    private function getResourceModel() : InventoryDataInterface
    {
        if ($this->resourceModel === null) {
            $resourceName = InventoryDataInterface::class;

            try {
                // Will try to fetch default implementation and fail in case of missing MSI modules or dependencies.
                $stockResolver = $this->config->getPreference(\Magento\InventorySalesApi\Api\StockResolverInterface::class);
                if (ltrim($stockResolver, '\\') === ltrim(\Magento\InventorySalesApi\Api\StockResolverInterface::class, '\\')) {
                    $resourceName = Deprecation::class;
                }
            } catch (\Exception $exception) {
                ; // Nothing to do, it's already kinda hacky to allow this deprecation fallback to happen.
            }

            $this->resourceModel = $this->objectManager->get($resourceName);
        }

        return $this->resourceModel;
    }
}
