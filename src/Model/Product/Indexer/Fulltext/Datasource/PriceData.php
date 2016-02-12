<?php

namespace Smile\ElasticSuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticSuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\PriceData as ResourceModel;

class PriceData implements DatasourceInterface
{
    /**
     * @var \Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\PriceData
     */
    private $resourceModel;

    /**
     *
     * @param \Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\PriceData $resourceModel
     */
    public function __construct(ResourceModel $resourceModel)
    {
        $this->resourceModel = $resourceModel;
    }

    /**
     * @inheritdoc
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\DatasourceInterface::addData()
     */
    public function addData($storeId, array $indexData)
    {
        $priceData = $this->resourceModel->loadPriceData($storeId, array_keys($indexData));
        foreach ($priceData as $priceDataRow) {
            $productId  = (int) $priceDataRow['entity_id'];
            $originalPrice = $priceDataRow['price'] !== null ? $priceDataRow['price'] : $priceDataRow['min_price'];
            $finalPrice    = $priceDataRow['final_price'] !== null ? $priceDataRow['final_price'] : $priceDataRow['min_price'];
            $indexData[$productId]['price'][] = [
                'price'             => $finalPrice,
                'original_price'    => $originalPrice,
                'is_discount'       => $finalPrice < $originalPrice,
                'customer_group_id' => $priceDataRow['customer_group_id'],
            ];
        }

        //var_dump($indexData);

        return $indexData;
    }
}
