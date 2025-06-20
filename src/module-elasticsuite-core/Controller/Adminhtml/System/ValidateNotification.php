<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Controller\Adminhtml\System;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Smile\ElasticsuiteCore\Helper\Cache;
use Smile\ElasticsuiteCore\Model\System\Condition\CanViewUpsellNotification;

/**
 * Validate notification.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ValidateNotification extends Action implements HttpPostActionInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Helper\Cache
     */
    private $cacheStorage;

    /**
     * ValidateNotification constructor.
     *
     * @param Action\Context $context      Action context.
     * @param Cache          $cacheStorage Cache Storage.
     */
    public function __construct(
        Action\Context                       $context,
        \Smile\ElasticsuiteCore\Helper\Cache $cacheStorage
    ) {
        parent::__construct($context);
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * Log information about the last shown advertisement
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $notificationCode = $this->getRequest()->getParam('notification_code', null);
        $cacheKey         = CanViewUpsellNotification::CACHE_PREFIX . $notificationCode;

        try {
            // Allow to dismiss for 24h.
            $this->cacheStorage->saveCache($cacheKey, serialize('log-exists'), [], 86400);
            $result = true;
        } catch (\Exception $e) {
            $result = false;
        }

        $responseContent = [
            'success'       => $result,
            'error_message' => '',
        ];
        $resultJson      = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        return $resultJson->setData($responseContent);
    }
}
