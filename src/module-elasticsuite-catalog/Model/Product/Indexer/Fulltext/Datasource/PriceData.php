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
     * @var PriceData\PriceDataReaderInterface[]
     */
    private $priceReaderPool = [];

    /**
     * Constructor.
     *
     * @param ResourceModel                        $resourceModel   Resource model
     * @param PriceData\PriceDataReaderInterface[] $priceReaderPool Price modifiers pool.
     */
    public function __construct(ResourceModel $resourceModel, $priceReaderPool = [])
    {
        $this->resourceModel     = $resourceModel;
        $this->priceReaderPool = $priceReaderPool;
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
            $productId     = (int) $priceDataRow['entity_id'];
            $productTypeId = $indexData[$productId]['type_id'];
            $priceModifier = $this->getPriceDataReader($productTypeId);

            $originalPrice = $priceModifier->getOriginalPrice($priceDataRow);
            $price         = $priceModifier->getPrice($priceDataRow);

            $indexData[$productId]['price'][] = [
                'price'             => $price,
                'original_price'    => $originalPrice,
                'is_discount'       => $price < $originalPrice,
                'customer_group_id' => $priceDataRow['customer_group_id'],
            ];

            if (!isset($indexData[$productId]['indexed_attributes'])) {
                $indexData[$productId]['indexed_attributes'] = ['price'];
            } elseif (!in_array('price', $indexData[$productId]['indexed_attributes'])) {
                // Add price only one time.
                $indexData[$productId]['indexed_attributes'][] = 'price';
            }
        }

        return $indexData;
    }

    /**
     * Retur
     * @param string $typeId Product type id.
     *
     * @return PriceData\PriceDataReaderInterface
     */
    private function getPriceDataReader($typeId)
    {
        $priceModifier = $this->priceReaderPool['default'];

        if (isset($this->priceReaderPool[$typeId])) {
            $priceModifier = $this->priceReaderPool[$typeId];
        }

        return $priceModifier;
    }
}
