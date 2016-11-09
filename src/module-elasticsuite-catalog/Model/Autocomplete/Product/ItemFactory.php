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
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Api\Data\ProductInterface;

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
     * Autocomplete image id (used for resize)
     */
    const AUTOCOMPLETE_IMAGE_ID = 'smile_elasticsuite_autocomplete_product_image';

    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager Object manager used to instantiate new item.
     * @param ImageHelper            $imageHelper   Catalog product image helper.
     */
    public function __construct(ObjectManagerInterface $objectManager, ImageHelper $imageHelper)
    {
        parent::__construct($objectManager);
        $this->imageHelper = $imageHelper;
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
            'title'       => html_entity_decode($product->getName()),
            'image'       => $this->getImageUrl($product),
            'url'         => $product->getProductUrl(),
            'price'       => $product->getFinalPrice(),
            'final_price' => $product->getPrice(),
        ];

        $data = array_merge($data, $productData);

        return $data;
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
        $this->imageHelper->init($product, self::AUTOCOMPLETE_IMAGE_ID);

        return $this->imageHelper->getUrl();
    }
}
