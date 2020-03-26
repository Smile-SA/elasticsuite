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
     * Constructor.
     *
     * @param \Smile\ElasticsuiteTracker\Model\ResourceModel\CustomerLink $customerLinkResource Resource model.
     * @param \Smile\ElasticsuiteTracker\Helper\Data                      $helper               Tracking Helper.
     * @param \Smile\ElasticsuiteTracker\Api\EventQueueInterface          $eventQueue           Event Queue.
     * @param \Magento\Customer\Model\Session                             $customerSession      Customer Session.
     */
    public function __construct(
        \Smile\ElasticsuiteTracker\Model\ResourceModel\CustomerLink $customerLinkResource,
        \Smile\ElasticsuiteTracker\Helper\Data $helper,
        \Smile\ElasticsuiteTracker\Api\EventQueueInterface $eventQueue,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->customerLinkResource = $customerLinkResource;
        $this->helper               = $helper;
        $this->customerSession      = $customerSession;
        $this->eventQueue           = $eventQueue;
    }

    /**
     * {@inheritdoc}
     */
    public function addEvent($eventData)
    {
        if ($this->helper->isEnabled()) {
            $this->eventQueue->addEvent($eventData);
            $this->addCustomerLink($eventData);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function anonymizeCustomerData(int $customerId, \DateTime $delay = null)
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
    private function addCustomerLink($eventData)
    {
        // The customerId is set in session if the Magento_Persistent module is enabled and a persistent session exists.
        if ($this->customerSession->getCustomerId() !== null) {
            $customerId = $this->customerSession->getCustomerId();
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
        }
    }
}
