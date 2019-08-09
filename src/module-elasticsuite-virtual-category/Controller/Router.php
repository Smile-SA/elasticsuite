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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteVirtualCategory\Model\Url;

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
     * Router Constructor
     *
     * @param ActionFactory         $actionFactory Action Factory
     * @param ManagerInterface      $eventManager  Event Manager
     * @param StoreManagerInterface $storeManager  Store Manager
     * @param Url                   $urlModel      Url Model
     */
    public function __construct(
        ActionFactory $actionFactory,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        Url $urlModel
    ) {
        $this->actionFactory = $actionFactory;
        $this->eventManager  = $eventManager;
        $this->storeManager  = $storeManager;
        $this->urlModel      = $urlModel;
    }

    /**
     * Validate and match Product Page and modify request
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
        $chunks = explode('/', $identifier);
        $productPath = array_pop($chunks);
        $categoryPath = implode('/', $chunks);
        if (!empty($categoryPath) && !empty($productPath)) {
            $this->eventManager->dispatch(
                'smile_elasticsuite_virtualcategory_controller_router_match_before',
                ['router' => $this, 'condition' => $condition]
            );
            $storeId = $this->storeManager->getStore()->getId();
            $productRewrite = $this->urlModel->getProductRewrite($productPath, $categoryPath, $storeId);
            if ($productRewrite) {
                $request->setAlias(UrlInterface::REWRITE_REQUEST_PATH_ALIAS, $productRewrite->getRequestPath());
                $request->setPathInfo('/' . $productRewrite->getTargetPath());

                return $this->actionFactory->create('Magento\Framework\App\Action\Forward');
            }
        }

        return $action;
    }
}
