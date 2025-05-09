<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Controller\Adminhtml\Healthcheck;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;
use Smile\ElasticsuiteCore\Model\Healthcheck\HealthcheckList;
use Magento\Framework\App\CacheInterface;

/**
 * Class Index.
 */
class Index extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Smile_ElasticsuiteCore::healthcheck';

    /** @var CacheInterface */
    private $cache;

    /**
     * Constructor.
     *
     * @param CacheInterface $cache   App cache.
     * @param Context        $context Context.
     *
     */
    public function __construct(
        CacheInterface $cache,
        Context $context
    ) {
        parent::__construct($context);
        $this->cache = $cache;
    }

    /**
     * @inheritdoc
     *
     * @return Page
     */
    public function execute(): Page
    {
        // Refresh the cache so the menu decorator is refreshed if need be.
        $this->cache->clean(HealthcheckList::CACHE_TAG);

        $breadMain = __('Healthcheck');

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend($breadMain);

        return $resultPage;
    }
}
