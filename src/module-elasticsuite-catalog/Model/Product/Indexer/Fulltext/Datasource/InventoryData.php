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
namespace Smile\ElasticSuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticSuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\InventoryData as ResourceModel;

/**
 * Datasource used to append inventory data to product during indexing.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class InventoryData implements DatasourceInterface
{
    /**
     * @var \Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\InventoryData
     */
    private $resourceModel;

    /**
     * Constructor.
     *
     * @param ResourceModel $resourceModel Resource model.
     */
    public function __construct(ResourceModel $resourceModel)
    {
        $this->resourceModel = $resourceModel;
    }

    /**
     * Add inventory data to the index data.
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        $inventoryData = $this->resourceModel->loadInventoryData($storeId, array_keys($indexData));

        foreach ($inventoryData as $inventoryDataRow) {
            $productId = (int) $inventoryDataRow['product_id'];
            $indexData[$productId]['stock'] = [
                'is_in_stock' => (bool) $inventoryDataRow['stock_status'],
                'qty'         => (int) $inventoryDataRow['qty'],
            ];
        }

        return $indexData;
    }
}
