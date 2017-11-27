<?php
/**
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Vladimir Bratukhin <insyon@gmail.com>
 * @copyright 2017 Smile
 */

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
     * {@inheritdoc}
     */
    public function addProductData($storeId, array $data)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addChildData($storeId, array $data, $childId)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addInventoryData($storeId, array $data)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addPriceData($storeId, array $data)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addCategoryData($storeId, array $data)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [];
    }
}
