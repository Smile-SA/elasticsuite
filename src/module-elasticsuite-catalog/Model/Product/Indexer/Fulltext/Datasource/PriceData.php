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
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\PriceData as ResourceModel;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Model\Config;

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
     * @var TaxCalculationInterface
     */
    private $taxCalculation;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var Config
     */
    private $taxConfig;

    /**
     * Constructor.
     *
     * @param ResourceModel                        $resourceModel   Resource model
     * @param PriceData\PriceDataReaderInterface[] $priceReaderPool Price modifiers pool.
     */
    public function __construct(
        ResourceModel $resourceModel,
        $priceReaderPool = [],
        TaxCalculationInterface $taxCalculation,
        PriceCurrencyInterface $priceCurrency,
        Config $taxConfig
    )
    {
        $this->resourceModel   = $resourceModel;
        $this->priceReaderPool = $priceReaderPool;
        $this->taxCalculation  = $taxCalculation;
        $this->priceCurrency   = $priceCurrency;
        $this->taxConfig       = $taxConfig;
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

            if ($this->taxConfig->needPriceConversion($storeId)) {
                $rate = $this->taxCalculation->getCalculatedRate($priceDataRow['tax_class_id']);
                $price = $this->priceCurrency->round($price * (1 + ($rate / 100)));
                $originalPrice = $this->priceCurrency->round($originalPrice * (1 + ($rate / 100)));
            }

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
