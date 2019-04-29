<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\ProductSorter;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Catalog\Helper\Image as ImageHelper;

/**
 * Product sorter item model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ItemDataFactory
{
    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * Constructor.
     *
     * @param ImageHelper $imageHelper Image helper.
     */
    public function __construct(ImageHelper $imageHelper)
    {
        $this->imageHelper = $imageHelper;
    }

    /**
     * Item data.
     *
     * @param ProductInterface $product Product.
     *
     * @return array
     */
    public function getData(ProductInterface $product)
    {
        $productItemData = [
            'id'          => $product->getId(),
            'sku'         => $product->getSku(),
            'name'        => $product->getName(),
            'price'       => $this->getProductPrice($product),
            'image'       => $this->getImageUrl($product),
            'score'       => $product->getDocumentScore(),
            'is_in_stock' => $this->isInStockProduct($product),
        ];

        return $productItemData;
    }

    /**
     * Returns current product sale price.
     *
     * @param ProductInterface $product Product.
     *
     * @return float
     */
    private function getProductPrice(ProductInterface $product)
    {
        $price    = 0;
        $document = $this->getDocumentSource($product);

        if (isset($document['price'])) {
            foreach ($document['price'] as $currentPrice) {
                if ((int) $price['customer_group_id'] === GroupInterface::NOT_LOGGED_IN_ID) {
                    $price = (float) $currentPrice['price'];
                }
            }
        }

        return $price;
    }

    /**
     * Returns current product stock status.
     *
     * @param ProductInterface $product Product.
     *
     * @return bool
     */
    private function isInStockProduct(ProductInterface $product)
    {
        $isInStock = false;
        $document = $this->getDocumentSource($product);
        if (isset($document['stock']['is_in_stock'])) {
            $isInStock = (bool) $document['stock']['is_in_stock'];
        }

        return $isInStock;
    }

    /**
     * Get resized image URL.
     *
     * @param ProductInterface $product Product.
     *
     * @return string
     */
    private function getImageUrl(ProductInterface $product)
    {
        $this->imageHelper->init($product, 'smile_elasticsuite_product_sorter_image');

        return $this->imageHelper->getUrl();
    }

    /**
     * Return the ES source document for the current product.
     *
     * @param ProductInterface $product Product.
     *
     * @return array
     */
    private function getDocumentSource(ProductInterface $product)
    {
        return $product->getDocumentSource() ? : [];
    }
}
