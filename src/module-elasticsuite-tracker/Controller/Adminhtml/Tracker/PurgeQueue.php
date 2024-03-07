<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteTracker\Controller\Adminhtml\Tracker;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Smile\ElasticsuiteTracker\Api\EventQueueInterface;

/**
 * Queue purge controller. Removes all invalid tracker events from the event queue table.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class PurgeQueue extends Action implements HttpPostActionInterface
{
    /** @var EventQueueInterface */
    private $eventQueue;

    /** @var JsonFactory */
    private $resultJsonFactory;

    /**
     * Constructor.
     *
     * @param EventQueueInterface $eventQueue        Event queue.
     * @param JsonFactory         $resultJsonFactory Result Json factory.
     * @param Context             $context           Context.
     */
    public function __construct(
        EventQueueInterface $eventQueue,
        JsonFactory $resultJsonFactory,
        Context $context
    ) {
        parent::__construct($context);
        $this->eventQueue = $eventQueue;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $result = [
            'success' => false,
            'errorMessage' => '',
        ];

        try {
            $this->eventQueue->purgeInvalidEvents(0);
            $result['success'] = true;
        } catch (\Exception $e) {
            $result['errorMessage'] = __('An error occurred while purging the tracker events queue.');
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData($result);
    }

    /**
     * Check if allowed to manage thesaurus
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Backend::smile_elasticsuite_tracker');
    }
}
