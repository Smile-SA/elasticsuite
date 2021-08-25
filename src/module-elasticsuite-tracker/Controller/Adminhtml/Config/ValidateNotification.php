<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Pierre Gauthier <pierre.gauthier@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteTracker\Controller\Adminhtml\Config;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Smile\ElasticsuiteTracker\Model\ResourceModel\Viewer\Logger as NotificationLogger;

/**
 * Validate notification.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Pierre Gauthier <pierre.gauthier@smile.fr>
 */
class ValidateNotification extends Action implements HttpPostActionInterface
{
    /**
     * @var NotificationLogger
     */
    private $notificationLogger;

    /**
     * ValidateNotification constructor.
     *
     * @param Action\Context     $context            Action context.
     * @param NotificationLogger $notificationLogger Notification logger.
     */
    public function __construct(
        Action\Context $context,
        NotificationLogger $notificationLogger
    ) {
        parent::__construct($context);
        $this->notificationLogger = $notificationLogger;
    }

    /**
     * Log information about the last shown advertisement
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $responseContent = [
            'success' => $this->notificationLogger->log($this->getRequest()->getParam('notification_code')),
            'error_message' => '',
        ];
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        return $resultJson->setData($responseContent);
    }
}
