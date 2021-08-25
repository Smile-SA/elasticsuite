<?php
/*
 * @package      Webcode_elasticsuite
 *
 * @author       Kostadin Bashev (bashev@webcode.bg)
 * @copyright    Copyright © 2021 Webcode Ltd. (https://webcode.bg/)
 * @license      See LICENSE.txt for license details.
 */

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\PriceData as ResourceModel;
use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;

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
     * @var \Magento\Catalog\Model\ProductRepository
     */
    private $productRepository;

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
     * @param ResourceModel $resourceModel Resource model
     * @param PriceData\PriceDataReaderInterface[] $priceReaderPool Price modifiers pool.
     */
    public function __construct(
        ProductRepository $productRepository,
        ResourceModel $resourceModel,
        $priceReaderPool = []
    ) {
        $this->productRepository = $productRepository;
        $this->resourceModel = $resourceModel;
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
            $productId = (int)$priceDataRow['entity_id'];
            $productTypeId = $indexData[$productId]['type_id'];
            $priceModifier = $this->getPriceDataReader($productTypeId);

            $originalPrice = $priceModifier->getOriginalPrice($priceDataRow);
            $price = $priceModifier->getPrice($priceDataRow);

            $isDiscount = $price < $originalPrice;
            if (in_array($productTypeId, [Grouped::TYPE_CODE, Configurable::TYPE_CODE])) {
                $product = $this->productRepository->getById($productId);

                $isDiscount = false;
                if ($productTypeId === Grouped::TYPE_CODE) {
                    $children = $product->getTypeInstance()->getAssociatedProducts($product);
                }

                if ($productTypeId === Configurable::TYPE_CODE) {
                    $children = $product->getTypeInstance()->getUsedProducts($product);
                }

                if (isset($children)) {
                    foreach ($children as $child) {
                        if ($child->getPriceInfo()->getPrice(FinalPrice::PRICE_CODE)->getAmount()->getValue()
                            < $child->getPriceInfo()->getPrice(RegularPrice::PRICE_CODE)->getAmount()->getValue()) {
                            $isDiscount = true;
                            break;
                        }
                    }
                }
            }

            $indexData[$productId]['price'][] = [
                'price' => (float)$price,
                'original_price' => (float)$originalPrice,
                'is_discount' => $isDiscount,
                'customer_group_id' => (int)$priceDataRow['customer_group_id'],
                'tax_class_id' => (int)$priceDataRow['tax_class_id'],
                'final_price' => (float)$priceDataRow['final_price'],
                'min_price' => (float)$priceDataRow['min_price'],
                'max_price' => (float)$priceDataRow['max_price'],
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
     *
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
