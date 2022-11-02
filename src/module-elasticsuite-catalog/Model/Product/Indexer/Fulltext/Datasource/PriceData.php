<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\PriceData as ResourceModel;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\AttributeData as AttributeResourceModel;

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
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\AttributeData
     */
    private $attributeResourceModel;

    /**
     * @var PriceData\PriceDataReaderInterface[]
     */
    private $priceReaderPool = [];
    /**
     * Constructor.
     *
     * @param ResourceModel                        $resourceModel          Resource model
     * @param AttributeResourceModel               $attributeResourceModel Attribute Resource model
     * @param PriceData\PriceDataReaderInterface[] $priceReaderPool        Price modifiers pool.
     */
    public function __construct(
        ResourceModel $resourceModel,
        AttributeResourceModel $attributeResourceModel,
        $priceReaderPool = []
    ) {
        $this->resourceModel            = $resourceModel;
        $this->priceReaderPool          = $priceReaderPool;
        $this->attributeResourceModel   = $attributeResourceModel;
    }

    /**
     * Add price data to the index data.
     *
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        $productIds = array_keys($indexData);
        $priceData = $this->resourceModel->loadPriceData($storeId, $productIds);
        $allChildrenIds = $this->attributeResourceModel->loadChildrens($productIds, $storeId);
        $childPriceData = $this->resourceModel->loadPriceData($storeId, array_keys($allChildrenIds));

        foreach ($priceData as $priceDataRow) {
            $productId     = (int) $priceDataRow['entity_id'];
            $productTypeId = $indexData[$productId]['type_id'];
            $priceModifier = $this->getPriceDataReader($productTypeId);

            $originalPrice = $priceModifier->getOriginalPrice($priceDataRow);
            $price         = $priceModifier->getPrice($priceDataRow);

            $isDiscount    = $price < $originalPrice;
            if (in_array($productTypeId, $this->attributeResourceModel->getCompositeTypes())) {
                $isDiscount = false;
                $priceModifier = $this->getPriceDataReader('default');
                foreach ($childPriceData as $childPrice) {
                    if ($childPrice['customer_group_id'] == $priceDataRow['customer_group_id']) {
                        if ($priceModifier->getPrice($childPrice) < $priceModifier->getOriginalPrice($childPrice)) {
                            $isDiscount = true;
                            break;
                        }
                    }
                }
            }

            $indexData[$productId]['price'][] = [
                'price'             => $price,
                'original_price'    => $originalPrice,
                'is_discount'       => $isDiscount,
                'customer_group_id' => (int) $priceDataRow['customer_group_id'],
                'tax_class_id'      => (int) $priceDataRow['tax_class_id'],
                'final_price'       => (float) $priceDataRow['final_price'],
                'min_price'         => (float) $priceDataRow['min_price'],
                'max_price'         => (float) $priceDataRow['max_price'],
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
