<?php
/**
 * _______________________________
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Searchandising Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile________________
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Apache License Version 2.0
 */
namespace Smile\Tracker\Block\Variables\Page;
use Magento\Framework\View\Element\Template;

/**
 * Class Base
 *
 * @package   Smile\Tracker\Block\Variables\Page
 * @copyright 2016 Smile
 */
class Catalog extends \Smile\Tracker\Block\Variables\Page\AbstractBlock
{
    /**
     * Catalog layer
     *
     * @var \Magento\Catalog\Model\Layer
     */
    protected $_catalogLayer;

    /**
     * Set the default template for page variable blocks
     *
     * @param Template\Context                      $context       The template context
     * @param \Magento\Framework\Json\Helper\Data   $jsonHelper    The Magento's JSON Helper
     * @param \Smile\Tracker\Helper\Data            $trackerHelper The Smile_Tracker helper
     * @param \Magento\Framework\Registry           $registry      Magento Core Registry
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver The Magento layer resolver
     * @param array                                 $data          The block data
     *
     * @return Catalog
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Smile\Tracker\Helper\Data $trackerHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        array $data = []
    ) {
        $this->_catalogLayer = $layerResolver->get();

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
            $this->getLayerVariables()
        );

        return $variables;
    }

    /**
     * Returns categories variables (id, name and path).
     *
     * @return array
     */
    public function getCategoryVariables()
    {
        $variables = array();

        if ($this->_registry->registry('current_category')) {
            $category = $this->_registry->registry('current_category');
            $variables['category.id']    = $category->getId();
            $variables['category.label'] = $category->getName();
            $variables['category.path']  = $category->getPath();
        }

        return $variables;
    }

    /**
     * Return list of the product relatedd variables (id, label, sku)
     *
     * @return array
     */
    public function getProductVariables()
    {
        $variables = array();

        if ($this->_registry->registry('current_product')) {
            $product = $this->_registry->registry('current_product');
            $variables['product.id'] = $product->getId();
            $variables['product.label'] = $product->getName();
            $variables['product.sku'] = $product->getSku();
        }
        return $variables;
    }

    /**
     * Return list of product list variables (pages, sort, display mode, filters)
     *
     * @return array
     */
    public function getLayerVariables()
    {
        $variables = array();

        $productListBlock = $this->getLayout()->getBlock('product_list_toolbar');

        if ($productListBlock && $productListBlock->getCollection()) {
            $variables['product_list.page_count']     = $productListBlock->getLastPageNum();
            $variables['product_list.product_count']  = $productListBlock->getTotalNum();
            $variables['product_list.current_page']   = $productListBlock->getCurrentPage();
            $variables['product_list.sort_order']     = $productListBlock->getCurrentOrder();
            $variables['product_list.sort_direction'] = $productListBlock->getCurrentDirection();
            $variables['product_list.display_mode']   = $productListBlock->getCurrentMode();
        }

        $layer = $this->_catalogLayer;

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

                $variables['product_list.filters.' . $identifier] = $filterValue;
            }
        }

        return $variables;
    }

}