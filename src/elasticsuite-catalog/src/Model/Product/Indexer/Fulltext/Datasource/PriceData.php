<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticSuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\PriceData as ResourceModel;

/**
 * Datasource used to append prices data to product during indexing.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class PriceData implements DatasourceInterface
{
    /**
     * @var \Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\PriceData
     */
    private $resourceModel;

    /**
     * Constructor.
     *
     * @param ResourceModel $resourceModel Resource model
     */
    public function __construct(ResourceModel $resourceModel)
    {
        $this->resourceModel = $resourceModel;
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
            $productId  = (int) $priceDataRow['entity_id'];

            $originalPrice = $priceDataRow['price'];
            if ($originalPrice === null) {
                $originalPrice = $priceDataRow['min_price'];
            }

            $finalPrice = $priceDataRow['final_price'];
            if ($finalPrice === null) {
                $finalPrice = $priceDataRow['min_price'];
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
}
