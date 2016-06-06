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

namespace Smile\ElasticsuiteCatalog\Model\Autocomplete\Product;

use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Helper\Product as ProductHelper;

/**
 * Create an autocomplete item from a product.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ItemFactory extends \Magento\Search\Model\Autocomplete\ItemFactory
{
    /**
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager Object manager used to instantiate new item.
     * @param ProductHelper          $productHelper Catalog product helper.
     */
    public function __construct(ObjectManagerInterface $objectManager, ProductHelper $productHelper)
    {
        parent::__construct($objectManager);
        $this->productHelper = $productHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data)
    {
        $data = $this->addProductData($data);
        unset($data['product']);

        return parent::create($data);
    }

    /**
     * Load product data and append them to the original data.
     *
     * @param array $data Autocomplete item data.
     *
     * @return array
     */
    private function addProductData($data)
    {
        $product = $data['product'];

        $productData = [
            'title'       => $product->getName(),
            'image'       => $this->productHelper->getSmallImageUrl($product),
            'url'         => $product->getProductUrl(),
            'price'       => $product->getFinalPrice(),
            'final_price' => $product->getPrice(),
        ];

        $data = array_merge($data, $productData);

        return $data;
    }
}
