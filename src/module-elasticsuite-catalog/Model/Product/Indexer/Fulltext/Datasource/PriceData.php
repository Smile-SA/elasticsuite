<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\PriceData as ResourceModel;
use Magento\Catalog\Model\Product\TypeFactory as ProductTypeFactory;

/**
 * Datasource used to append prices data to product during indexing.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class PriceData implements DatasourceInterface
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\PriceData
     */
    private $resourceModel;

    /**
     * @var ProductType
     */
    private $productType;

    /**
     * Constructor.
     *
     * @param ResourceModel      $resourceModel      Resource model
     * @param ProductTypeFactory $productTypeFactory Product type factory (used to detect products types).
     */
    public function __construct(ResourceModel $resourceModel, ProductTypeFactory $productTypeFactory)
    {
        $this->resourceModel = $resourceModel;
        $this->productType   = $productTypeFactory->create();
    }

    /**
     * Add price data to the index data.
     *
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        $priceData = $this->resourceModel->loadPriceData($storeId, array_keys($indexData));

        foreach ($priceData as $priceDataRow) {
            $productId = (int) $priceDataRow['entity_id'];
            $isOriginalPriceReliable = $this->isOriginalPriceReliable($indexData[$productId]['type_id']);

            $originalPrice = $priceDataRow['min_price'];
            $finalPrice    = $priceDataRow['min_price'];

            if ($isOriginalPriceReliable) {
                if ($priceDataRow['price']) {
                    $originalPrice = $priceDataRow['price'];
                }
                if ($priceDataRow['final_price']) {
                    $finalPrice = $priceDataRow['final_price'];
                }
            }

            $indexData[$productId]['price'][] = [
                'price'             => $finalPrice,
                'original_price'    => $originalPrice,
                'is_discount'       => $finalPrice < $originalPrice,
                'customer_group_id' => $priceDataRow['customer_group_id'],
            ];
        }

        return $indexData;
    }

    /**
     * Price into indexed is not reliable for composite type.
     * This method detects this.
     *
     * @param string $productTypeId Product type id.
     *
     * @return boolean
     */
    private function isOriginalPriceReliable($productTypeId)
    {
        return !in_array($productTypeId, $this->productType->getCompositeTypes());
    }
}
