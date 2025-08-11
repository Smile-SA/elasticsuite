<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteTracker\Block\Variables\Page;

use Magento\Catalog\Model\Category;
use Magento\Framework\View\Element\Template;
use Smile\ElasticsuiteCatalog\Block\Navigation;

/**
 * Catalog variables block for page tracking, exposes all catalog tracking variables
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Catalog extends \Smile\ElasticsuiteTracker\Block\Variables\Page\AbstractBlock
{
    /**
     * Catalog layer
     *
     * @var \Magento\Catalog\Model\Layer
     */
    private $catalogLayer;

    /**
     * Category resource
     *
     * @var \Magento\Catalog\Model\ResourceModel\Category
     */
    private $categoryResource;

    /**
     * Category names.
     *
     * @var array
     */
    private $categoryNames = [];

    /**
     * Set the default template for page variable blocks
     *
     * @param Template\Context                              $context          The template context
     * @param \Magento\Framework\Json\Helper\Data           $jsonHelper       The Magento's JSON Helper
     * @param \Smile\ElasticsuiteTracker\Helper\Data        $trackerHelper    The Smile Tracker helper
     * @param \Magento\Framework\Registry                   $registry         Magento Core Registry
     * @param \Magento\Catalog\Model\Layer\Resolver         $layerResolver    The Magento layer resolver
     * @param \Magento\Catalog\Model\ResourceModel\Category $categoryResource Category resource
     * @param array                                         $data             The block data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Smile\ElasticsuiteTracker\Helper\Data $trackerHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Model\ResourceModel\Category $categoryResource,
        array $data = []
    ) {
        $this->catalogLayer     = $layerResolver->get();
        $this->categoryResource = $categoryResource;

        parent::__construct($context, $jsonHelper, $trackerHelper, $registry, $data);
    }

    /**
     * Return the list of catalog related variables.
     *
     * @return array
     */
    public function getVariables()
    {
        $variables = array_merge(
            $this->getCategoryVariables(),
            $this->getProductVariables(),
            $this->getProductListVariables(),
            $this->getLayerVariables()
        );

        return $variables;
    }

    /**
     * Returns categories variables (id, name and path).
     *
     * @return array
     */
    private function getCategoryVariables()
    {
        $variables = [];

        if ($this->registry->registry('current_category')) {
            /** @var Category $category */
            $category = $this->registry->registry('current_category');
            $variables['category.id']    = $category->getId();
            $variables['category.label'] = $category->getName();
            $variables['category.path']  = $category->getPath();
            $variables['category.breadcrumb'] = $this->getCategoryBreadcrumb($category);
        }

        return $variables;
    }

    /**
     * Return list of the product relatedd variables (id, label, sku)
     *
     * @return array
     */
    private function getProductVariables()
    {
        $variables = [];

        if ($this->registry->registry('current_product')) {
            $product = $this->registry->registry('current_product');
            $variables['product.id'] = $product->getId();
            $variables['product.label'] = $product->getName();
            $variables['product.sku'] = $product->getSku();
        }

        $productListBlock = $this->getProductListBlock();
        $index = 0;

        if ($productListBlock !== null && $productListBlock->getCollection()) {
            foreach ($productListBlock->getCollection() as $product) {
                $prefix = 'product_display.' . $index;
                $variables[$prefix . '.id']    = $product->getId();
                $variables[$prefix . '.sku']   = $product->getSku();
                $variables[$prefix . '.price'] = $product->getFinalPrice();
                $variables[$prefix . '.label'] = $product->getName();
                $index++;
            }
        }

        return $variables;
    }

    /**
     * Return list of product list variables (pages, sort, display mode, filters)
     *
     * @return array
     */
    private function getLayerVariables()
    {
        $variables = [];
        $layer     = $this->catalogLayer;

        if ($layer) {
            $layerState = $layer->getState();
            foreach ($layerState->getFilters() as $currentFilter) {
                $identifier = $currentFilter->getRequestVar();

                if ($currentFilter->getFilter()) {
                    $identifier = $currentFilter->getFilter()->getRequestVar();
                }

                $filterValue = $this->getRequest()->getParam($identifier, '');
                if (is_array($filterValue)) {
                    $filterValue = implode('|', $filterValue);
                }
                $variables['product_list.filters.' . $identifier] = html_entity_decode($filterValue);
            }
        }

        return $variables;
    }

    /**
     * Return list of product list variables (pages, sort, display mode)
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getProductListVariables()
    {
        $variables = [];

        $productListBlock = $this->getProductListBlock();

        if ($productListBlock !== null && $productListBlock->getCollection()) {
            $variables['product_list.page_count'] = $productListBlock->getLastPageNum();
            $variables['product_list.product_count'] = $productListBlock->getTotalNum();
            $variables['product_list.current_page'] = $productListBlock->getCurrentPage();
            $variables['product_list.sort_order'] = $productListBlock->getCurrentOrder();
            $variables['product_list.sort_direction'] = $productListBlock->getCurrentDirection();
            $variables['product_list.display_mode'] = $productListBlock->getCurrentMode();
        }

        return $variables;
    }

    /**
     * Retrieve the product list block from the layout.
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     */
    private function getProductListBlock()
    {
        $productListBlock = $this->getLayout()->getBlock('product_list_toolbar');

        return is_object($productListBlock) ? $productListBlock : null;
    }

    /**
     * Return a mini-breadcrumb for a category
     *
     * @param \Magento\Catalog\Model\Category $category The category
     *
     * @return string
     */
    private function getCategoryBreadcrumb(\Magento\Catalog\Model\Category $category)
    {
        $path    = $category->getPath();
        $rawPath = explode('/', $path);

        // First occurence is root category (1), second is root category of store.
        $rawPath = array_slice($rawPath, 2);

        // Last occurence is the category displayed.
        array_pop($rawPath);

        $breadcrumb = [];
        foreach ($rawPath as $categoryId) {
            $breadcrumb[] = html_entity_decode($this->getCategoryNameById($categoryId, $category->getStoreId()));
        }

        return implode('|', $breadcrumb);
    }

    /**
     * Retrieve a category name by it's id, and store it in local cache
     *
     * @param int $categoryId The category Id
     * @param int $storeId    The store Id
     *
     * @return string
     */
    private function getCategoryNameById($categoryId, $storeId)
    {
        if (!isset($this->categoryNames[$categoryId])) {
            $this->categoryNames[$categoryId]
                = $this->categoryResource->getAttributeRawValue($categoryId, "name", $storeId);
        }

        return $this->categoryNames[$categoryId];
    }
}
