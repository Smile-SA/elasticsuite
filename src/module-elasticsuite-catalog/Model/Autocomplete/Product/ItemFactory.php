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

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Pricing\Render;

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
     * @var \Magento\Framework\Pricing\Render
     */
    private $priceRenderer;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager Object manager used to instantiate new item.
     * @param ImageHelper            $imageHelper   Catalog product image helper.
     * @param Render                 $priceRenderer Catalog product price renderer.
     */
    public function __construct(ObjectManagerInterface $objectManager, ImageHelper $imageHelper, Render $priceRenderer)
    {
        parent::__construct($objectManager);
        $this->imageHelper = $imageHelper;
        $this->priceRenderer = $priceRenderer;
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data)
    {
        $data = $this->addProductData($data);
        unset($data['product'], $data['additional_attributes']);

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
            'image'       => $this->getImageUrl($product),
            'url'         => $product->getProductUrl(),
            'price'       => $this->renderProductPrice($product, \Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE),
        ];
        $additionalAttributes = $data['additional_attributes'];
        foreach ($additionalAttributes as $additionalAttributeKey => $additionalAttribute) {
            $productData[$additionalAttributeKey] = $product->getData($additionalAttribute);
        }

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


    /**
     * Renders product price.
     *
     * @param \Magento\Catalog\Model\Product $product   The product
     * @param string                         $priceCode The Price Code to render
     *
     * @return string
     */
    private function renderProductPrice(\Magento\Catalog\Model\Product $product, $priceCode)
    {
        $priceRender = $this->getPriceRenderer();

        $price = $product->getData($priceCode);

        if ($priceRender) {
            $price = $priceRender->render(
                $priceCode,
                $product,
                [
                    'include_container' => false,
                    'display_minimal_price' => true,
                    'zone' => Render::ZONE_ITEM_LIST,
                    'list_category_page' => true,
                ]
            );
        }

        return $price;
    }

    /**
     * Retrieve Price Renderer Block
     *
     * @return bool|\Magento\Framework\View\Element\BlockInterface
     */
    private function getPriceRenderer()
    {
        /** @var \Magento\Framework\View\LayoutInterface $layout */
        $layout = $this->objectManager->get('\Magento\Framework\View\LayoutInterface');
        $layout->getUpdate()->addHandle('default');
        $priceRenderer = $layout->getBlock('product.price.render.default');

        if (!$priceRenderer) {
            $priceRenderer = $layout->createBlock(
                'Magento\Framework\Pricing\Render',
                'product.price.render.default',
                ['data' => ['price_render_handle' => 'catalog_product_prices']]
            );
        }

        return $priceRenderer;
    }
}
