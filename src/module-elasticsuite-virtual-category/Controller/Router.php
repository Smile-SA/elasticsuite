<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Controller;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteVirtualCategory\Model\Url;
use Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root as VirtualCategoryRoot;

/**
 * Router used when accessing a product via an url containing a virtual category request path.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Router implements RouterInterface
{
    /**
     * @var ActionFactory
     */
    private $actionFactory;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Url
     */
    private $urlModel;

    /**
     * @var VirtualCategoryRoot
     */
    private $virtualCategoryRoot;

    /**
     * Router Constructor
     *
     * @param ActionFactory         $actionFactory       Action Factory
     * @param ManagerInterface      $eventManager        Event Manager
     * @param StoreManagerInterface $storeManager        Store Manager
     * @param Url                   $urlModel            Url Model
     * @param VirtualCategoryRoot   $virtualCategoryRoot Virtual Category Root
     */
    public function __construct(
        ActionFactory $actionFactory,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        Url $urlModel,
        VirtualCategoryRoot $virtualCategoryRoot
    ) {
        $this->actionFactory = $actionFactory;
        $this->eventManager  = $eventManager;
        $this->storeManager  = $storeManager;
        $this->urlModel      = $urlModel;
        $this->virtualCategoryRoot  = $virtualCategoryRoot;
    }

    /**
     * Validate and match Product or Category Page under virtual category navigation and modify request
     *
     * @param RequestInterface $request The Request
     *
     * @return ActionInterface|null
     */
    public function match(RequestInterface $request): ?ActionInterface
    {
        $action = null;

        $identifier = trim($request->getPathInfo(), '/');
        $condition = new DataObject(['identifier' => $identifier]);
        $appliedRoot = $this->getAppliedVirtualCategoryRoot($identifier);

        $this->eventManager->dispatch(
            'smile_elasticsuite_virtualcategory_controller_router_match_before',
            ['router' => $this, 'condition' => $condition]
        );

        if ($appliedRoot && $appliedRoot->getId()) {
            $this->virtualCategoryRoot->setAppliedRootCategory($appliedRoot);
        }

        $productRewrite = $this->getProductRewrite($identifier);
        if ($productRewrite) {
            $request->setAlias(UrlInterface::REWRITE_REQUEST_PATH_ALIAS, $productRewrite->getRequestPath());
            $request->setPathInfo('/' . $productRewrite->getTargetPath());

            return $this->actionFactory->create('Magento\Framework\App\Action\Forward');
        }

        $categoryRewrite = $this->getCategoryRewrite($identifier);
        if ($categoryRewrite) {
            $request->setAlias(UrlInterface::REWRITE_REQUEST_PATH_ALIAS, $identifier);
            $request->setPathInfo('/' . $categoryRewrite->getTargetPath());

            return $this->actionFactory->create('Magento\Framework\App\Action\Forward');
        }

        return $action;
    }

    /**
     * Check if the current request could match a product.
     *
     * @param string $identifier Current identifier
     *
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite|null
     */
    private function getProductRewrite($identifier)
    {
        $chunks       = explode('/', $identifier);
        $productPath  = array_pop($chunks);
        $categoryPath = implode('/', $chunks);
        $storeId      = $this->storeManager->getStore()->getId();

        return $this->urlModel->getProductRewrite($productPath, $categoryPath, $storeId);
    }

    /**
     * Check if the current request could match a category under a virtual category subtree.
     *
     * @param string $identifier Current identifier
     *
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite|null
     */
    private function getCategoryRewrite($identifier)
    {
        $chunks       = explode('/', $identifier);
        $categoryPath = array_pop($chunks);
        $storeId      = $this->storeManager->getStore()->getId();

        return $this->urlModel->getCategoryRewrite($categoryPath, $storeId);
    }

    /**
     * Retrieve the current applied virtual category root.
     *
     * @param string $identifier Current identifier
     *
     * @return CategoryInterface
     */
    private function getAppliedVirtualCategoryRoot($identifier)
    {
        $urlKeys = explode('/', $identifier);
        array_pop($urlKeys);

        return $this->virtualCategoryRoot->getByUrlKeys($urlKeys);
    }
}
