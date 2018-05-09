<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\CatalogSearch;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\CatalogSearch\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;

/**
 * Plugin which is responsible to redirect to a product page when only one result is found.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ResultPlugin
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
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * RedirectIfOneResult constructor.
     *
     * @param \Magento\Catalog\Model\Layer\Resolver              $layerResolver       Layer Resolver
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig         Scope Configuration
     * @param \Magento\CatalogSearch\Helper\Data                 $catalogSearchHelper Catalog Search Helper
     * @param \Magento\Framework\Message\ManagerInterface        $messageManager      Message Manager
     * @param \Magento\Framework\Controller\ResultFactory        $resultFactory       Result Interface Factory
     */
    public function __construct(
        Resolver $layerResolver,
        ScopeConfigInterface $scopeConfig,
        Data $catalogSearchHelper,
        ManagerInterface $messageManager,
        ResultFactory $resultFactory
    ) {
        $this->layerResolver  = $layerResolver;
        $this->scopeConfig    = $scopeConfig;
        $this->messageManager = $messageManager;
        $this->helper         = $catalogSearchHelper;
        $this->resultFactory  = $resultFactory;
    }

    /**
     * Process a redirect to the product page if there is only one result for a given search.
     *
     * @param \Magento\CatalogSearch\Controller\Result\Index $subject The CatalogSearch Result Controller
     * @param \Closure                                       $proceed The execute method
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function aroundExecute(
        \Magento\CatalogSearch\Controller\Result\Index $subject,
        \Closure $proceed
    ) {
        $proceed();

        if (!$subject->getResponse()->isRedirect() && $this->scopeConfig->isSetFlag(self::REDIRECT_SETTINGS_CONFIG_XML_FLAG)) {
            $layer      = $this->layerResolver->get();
            $layerState = $layer->getState();

            if (count($layerState->getFilters()) === 0) {
                $productCollection = $layer->getProductCollection();
                if ($productCollection->getCurPage() === 1 && $productCollection->getSize() === 1) {
                    /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
                    $product = $productCollection->getFirstItem();
                    if ($product->getId()) {
                        $this->addRedirectMessage($product);
                        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                        $result->setUrl($product->getProductUrl());

                        return $result;
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
        $message = __("%1 is the only product matching your '%2' search.", $product->getName(), $this->helper->getEscapedQueryText());
        $this->messageManager->addSuccessMessage($message);
    }
}
