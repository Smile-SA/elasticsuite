<?php

namespace Smile\ElasticSuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticSuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\CategoryData as ResourceModel;

class CategoryData implements DatasourceInterface
{
    /**
     * @var \Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\CategoryData
     */
    private $resourceModel;

    /**
     *
     * @param \Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\CategoryData $resourceModel
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
        $categoryData = $this->resourceModel->loadCategoryData($storeId, array_keys($indexData));

        foreach ($categoryData as $categoryDataRow) {
            $productId = (int) $categoryDataRow['product_id'];
            $indexData[$productId]['categories'][] = [
                'category_id' => $categoryDataRow['category_id'],
                'is_parent'   => $categoryDataRow['is_parent'],
                'position'    => $categoryDataRow['position'],
            ];
        }

        return $indexData;
    }
}
