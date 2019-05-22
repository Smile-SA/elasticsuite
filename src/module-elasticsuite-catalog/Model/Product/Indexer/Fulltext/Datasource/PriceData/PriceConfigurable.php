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

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource\PriceData;

/**
 * Price data parser used for most configurable products.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class PriceConfigurable implements PriceDataReaderInterface
{
    /** @var \Magento\Catalog\Model\ProductRepository */
    protected $productRepository;

    /** @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\PriceData */
    protected $resourceModel;

    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\PriceData $resourceModel)
    {
        $this->productRepository = $productRepository;
        $this->resourceModel = $resourceModel;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrice($priceData)
    {
        return $priceData['min_price'];
    }

    /**
     * {@inheritDoc}
     */
    public function getOriginalPrice($priceData)
    {
        return $priceData['max_price'];
    }

    public function getIsDiscount($priceData, $productId, $storeId, $customerGroupId) {
        $configurable = $this->productRepository->getById($productId, false, $storeId);
        $ids = [];
        foreach($configurable->getTypeInstance()->getUsedProducts($configurable) as $product) {
            $ids[] = $product->getId();
        }

        $priceData = $this->resourceModel->loadPriceData($storeId, $ids);

        foreach($priceData as $priceRow) {
            if ($priceRow['customer_group_id'] != $customerGroupId) {
                continue;
            }

            $price =  $priceRow['final_price'] ?? $priceRow['price'] ?? 0;
            $originalPrice = $priceRow['price'] ?? 0;

            var_dump($price . ' ' . $originalPrice);
            if ($price < $originalPrice) {
                return true;
            }
        }
        return false; // no simple with discount found
    }



}
