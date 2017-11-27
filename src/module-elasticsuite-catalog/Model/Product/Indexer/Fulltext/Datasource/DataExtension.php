<?php

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use \Smile\ElasticsuiteCatalog\Api\ProductDataExtensionInterface;

/**
 * Class AttributeDataExtension
 *
 * @package Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource
 *
 * @author Vladimir Bratukhin <insyon@gmail.com>
 */
class DataExtension implements ProductDataExtensionInterface
{
    /**
     * @inheritdoc
     */
    public function addProductData(array $data)
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addChildData(array $data, $childId)
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addInventoryData(array $data)
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addPriceData(array $data)
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addCategoryData(array $data)
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return [];
    }
}
