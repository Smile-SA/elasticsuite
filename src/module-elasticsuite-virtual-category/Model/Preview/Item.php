<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\Preview;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Catalog\Helper\Image as ImageHelper;

/**
 * Virtual category preview item model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Item
{
    /**
     * @var ProductInterface
     */
    private $product;

    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * Constructor.
     *
     * @param ProductInterface $product     Item product.
     * @param ImageHelper      $imageHelper Image helper.
     */
    public function __construct(ProductInterface $product, ImageHelper $imageHelper)
    {
        $this->product     = $product;
        $this->imageHelper = $imageHelper;
    }

    /**
     * Item data.
     *
     * @return array
     */
    public function getData()
    {
        $productItemData = [
            'id'          => $this->product->getId(),
            'name'        => $this->product->getName(),
            'price'       => $this->getProductPrice(),
            'image'       => $this->getImageUrl($this->product),
            'score'       => $this->product->getDocumentScore(),
            'is_in_stock' => $this->isInStockProduct(),
        ];

        return $productItemData;
    }

    /**
     * Returns current product sale price.
     *
     * @return float
     */
    private function getProductPrice()
    {
        $price    = 0;
        $document = $this->getDocumentSource();

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
     * @return bool
     */
    private function isInStockProduct()
    {
        $isInStock = false;
        $document = $this->getDocumentSource();
        if (isset($document['stock']['is_in_stock'])) {
            $isInStock = (bool) $document['stock']['is_in_stock'];
        }

        return $isInStock;
    }

    /**
     * Get resized image URL.
     *
     * @param ProductInterface $product Current product.
     *
     * @return string
     */
    private function getImageUrl($product)
    {
        $this->imageHelper->init($product, 'smile_elasticsuitevirtualcategory_preview');

        return $this->imageHelper->getUrl();
    }

    /**
     * Return the ES source document for the current product.
     *
     * @return array
     */
    private function getDocumentSource()
    {
        return $this->product->getDocumentSource() ? : [];
    }
}
