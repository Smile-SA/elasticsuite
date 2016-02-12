<?php

namespace Smile\ElasticSuiteCatalog\Model\Product\Indexer\Fulltext\Action;


use Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Action\Full as ResourceModel;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

class Full
{
    /**
     * @var \Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Action\Full
     */
    private $resourceModel;

    public function __construct(ResourceModel $resourceModel)
    {
        $this->resourceModel = $resourceModel;
    }

    /**
     *
     * @param int   $storeId
     * @param array $productIds
     *
     * @return Generator
     */
    public function rebuildStoreIndex($storeId, $productIds = null)
    {
        $lastProductId = 0;

        do {
            $products = $this->getSearchableProducts($storeId, $productIds, $lastProductId);

            foreach ($products as $productData) {
                $lastProductId = (int) $productData['entity_id'];
                yield $lastProductId => $productData;
            }

        } while(!empty($products));
    }

    /**
     *
     * @param int    $storeId
     * @param string $productIds
     * @param int    $lastProductId
     * @param int    $limit
     *
     * @return array
     */
    private function getSearchableProducts($storeId, $productIds = null, $lastProductId = 0, $limit = 100)
    {
        return $this->resourceModel->getSearchableProducts($storeId, $productIds, $lastProductId, $limit);
    }
}