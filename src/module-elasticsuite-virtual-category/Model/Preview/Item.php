<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteVirtualCategory\Model\Preview;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ProductImageHelper;
use Magento\Catalog\Helper\Product as ProductHelper;

/**
 * Virtual category preview item model.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Item
{
    /**
     * @var ProductInterface
     */
    private $product;

    /**
     *
     * @var ProductHelper $productHelper
     */
    private $productHelper;

    /**
     * Constructor.
     *
     * @param ProductInterface $product       Item product.
     * @param ProductHelper    $productHelper Product helper.
     */
    public function __construct(ProductInterface $product, ProductHelper $productHelper)
    {
        $this->product            = $product;
        $this->productHelper      = $productHelper;
    }

    /**
     * Item data.
     *
     * @return array
     */
    public function getData()
    {
        $productItemData = [
            'id'    => $this->product->getId(),
            'name'  => $this->product->getName(),
            'price' => $this->product->getPrice(),
            'image' => $this->productHelper->getSmallImageUrl($this->product), // @todo: Use a resized image.
            'score' => $this->product->getDocumentScore(),
        ];

        return $productItemData;
    }
}
