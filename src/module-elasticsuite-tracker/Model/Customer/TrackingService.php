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
namespace Smile\ElasticsuiteTracker\Model\Customer;

/**
 * Customer Tracking Service.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class TrackingService implements \Smile\ElasticsuiteTracker\Api\CustomerTrackingServiceInterface
{
    /**
     * @var \Smile\ElasticsuiteTracker\Model\ResourceModel\CustomerLink
     */
    private $customerLinkResource;

    /**
     * @var \Smile\ElasticsuiteTracker\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Smile\ElasticsuiteTracker\Api\EventQueueInterface
     */
    private $eventQueue;

    /**
     * @var \Smile\ElasticsuiteTracker\Helper\BotDetector
     */
    private $botDetector;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteTracker\Model\ResourceModel\CustomerLink $customerLinkResource Resource model.
     * @param \Smile\ElasticsuiteTracker\Helper\Data                      $helper               Tracking Helper.
     * @param \Smile\ElasticsuiteTracker\Api\EventQueueInterface          $eventQueue           Event Queue.
     * @param \Magento\Customer\Model\Session                             $customerSession      Customer Session.
     * @param \Smile\ElasticsuiteTracker\Helper\BotDetector               $botDetector          Bot detector.
     */
    public function __construct(
        \Smile\ElasticsuiteTracker\Model\ResourceModel\CustomerLink $customerLinkResource,
        \Smile\ElasticsuiteTracker\Helper\Data $helper,
        \Smile\ElasticsuiteTracker\Api\EventQueueInterface $eventQueue,
        \Magento\Customer\Model\Session $customerSession,
        \Smile\ElasticsuiteTracker\Helper\BotDetector $botDetector
    ) {
        $this->customerLinkResource = $customerLinkResource;
        $this->helper               = $helper;
        $this->customerSession      = $customerSession;
        $this->eventQueue           = $eventQueue;
        $this->botDetector          = $botDetector;
    }

    /**
     * {@inheritDoc}
     */
    public function hit($eventData): void
    {
        $this->addEvent($eventData);
    }

    /**
     * {@inheritdoc}
     */
    public function addEvent($eventData)
    {
        if ($this->helper->isEnabled()) {
            if ($this->helper->isFilteringBotHits() && $this->botDetector->isBot()) {
                return;
            }
            $this->addCustomerLink($eventData);
            $this->eventQueue->addEvent($eventData);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function anonymizeCustomerData(int $customerId, ?\DateTime $delay = null)
    {
        // If delay is not null, apply this delay to the customer data.
        if ($delay !== null) {
            $this->customerLinkResource->setDeleteAfter($customerId, $delay);
        }

        // If delay is null, process deletion of all this customer data.
        if ($delay === null) {
            $this->customerLinkResource->deleteByCustomerId($customerId);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteExpired()
    {
        $date = new \DateTime();
        $this->customerLinkResource->deleteByDeleteAfter($date);
    }

    /**
     * {@inheritdoc}
     */
    public function getVisitorIds(int $customerId)
    {
        return $this->customerLinkResource->getVisitorIds($customerId);
    }

    /**
     * Save customer link if the current customer is logged in.
     *
     * @param array $eventData Event
     */
    private function addCustomerLink(&$eventData)
    {
        // The customerId should be sent by the frontend, if any.
        $customerId = $eventData['customer']['id'] ?? null;
        if ($customerId !== null && ((int) $customerId > 0)) {
            $sessionId  = $eventData['session']['uid'] ?? null;
            $visitorId  = $eventData['session']['vid'] ?? null;

            if ($sessionId && $visitorId) {
                $data = [
                    'customer_id' => $customerId,
                    'session_id'  => $sessionId,
                    'visitor_id'  => $visitorId,
                ];

                if ($this->helper->isAnonymizationEnabled()) {
                    $date = new \DateTime();
                    $date->modify(sprintf('+ %d days', $this->helper->getAnonymizationDelay()));
                    $data['delete_after'] = $date;
                }

                $this->customerLinkResource->saveLink($data);
            }
            unset($eventData['customer']['id']); // Do not persist the customer_id in ES index to preserve anonymization.
        }
    }
}
