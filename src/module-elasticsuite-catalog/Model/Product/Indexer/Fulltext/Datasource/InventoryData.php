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
namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\InventoryData as ResourceModel;
use Smile\ElasticsuiteCatalog\Api\ProductDataExtensionInterfaceFactory;

/**
 * Datasource used to append inventory data to product during indexing.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class InventoryData extends AbstractExtensible implements DatasourceInterface
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\InventoryData
     */
    private $resourceModel;

    /**
     * Constructor.
     *
     * @param ProductDataExtensionInterfaceFactory $dataExtensionInterfaceFactory DataExtension factory
     * @param ResourceModel                        $resourceModel                 Resource model
     */
    public function __construct(
        ProductDataExtensionInterfaceFactory $dataExtensionInterfaceFactory,
        ResourceModel $resourceModel
    ) {
        parent::__construct($dataExtensionInterfaceFactory);
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
            $this->getDataExtension($indexData[$productId])
                 ->addInventoryData($storeId, $inventoryDataRow);
        }

        return $indexData;
    }
}
