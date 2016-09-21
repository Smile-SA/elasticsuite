<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\CatalogSearch\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Message\ManagerInterface;

/**
 * Observer that redirect to the product page if this is the only search result.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class RedirectIfOneResult implements ObserverInterface
{
    /**
     * Constant for configuration field location.
     */
    const REDIRECT_SETTINGS_CONFIG_XML_FLAG = 'smile_elasticsuite_catalogsearch_settings/catalogsearch/redirect_if_one_result';

    /**
     * Catalog Layer Resolver
     *
     * @var Resolver
     */
    private $layerResolver;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\CatalogSearch\Helper\Data
     */
    private $helper;

    /**
     * RedirectIfOneResult constructor.
     *
     * @param \Magento\Catalog\Model\Layer\Resolver              $layerResolver       Layer Resolver
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig         Scope Configuration
     * @param \Magento\CatalogSearch\Helper\Data                 $catalogSearchHelper Catalog Search Helper
     * @param \Magento\Framework\Message\ManagerInterface        $messageManager      Message Manager
     */
    public function __construct(
        Resolver $layerResolver,
        ScopeConfigInterface $scopeConfig,
        Data $catalogSearchHelper,
        ManagerInterface $messageManager
    ) {
        $this->layerResolver  = $layerResolver;
        $this->scopeConfig    = $scopeConfig;
        $this->messageManager = $messageManager;
        $this->helper         = $catalogSearchHelper;
    }

    /**
     * Process redirect to the product page if this is the only search result.
     *
     * @param Observer $observer The observer
     * @event controller_action_postdispatch_catalogsearch_result_index
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->scopeConfig->isSetFlag(self::REDIRECT_SETTINGS_CONFIG_XML_FLAG)) {
            $layer      = $this->layerResolver->get();
            $layerState = $layer->getState();

            if (count($layerState->getFilters()) === 0) {
                $productCollection = $layer->getProductCollection();
                if ($productCollection->getCurPage() === 1 && $productCollection->getSize() === 1) {
                    /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
                    $product = $productCollection->getFirstItem();
                    if ($product->getId()) {
                        $this->addRedirectMessage($product);
                        $observer->getControllerAction()->getResponse()->setRedirect($product->getProductUrl());
                    }
                }
            }
        }
    }

    /**
     * Append message to the customer session to inform he has been redirected
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product The product being redirected to.
     */
    private function addRedirectMessage(ProductInterface $product)
    {
        $message = __("%1 is the only product matching your '%2' research.", $product->getName(), $this->helper->getEscapedQueryText());
        $this->messageManager->addSuccessMessage($message);
    }
}
