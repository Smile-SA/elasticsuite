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
namespace Smile\ElasticsuiteTracker\Model;

use DateTime;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Tracking Indices Manager
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CustomerDataTrackingManager
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @param CustomerSession $customerSession Customer session
     */
    public function __construct(CustomerSession $customerSession)
    {
        $this->customerSession = $customerSession;
    }

    /**
     * Get customer data to track.
     *
     * @return array
     */
    public function getCustomerDataToTrack()
    {
        $variables = [
            'group_id' => \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID,
        ];

        if (!$this->customerSession->getId()) {
            return $variables;
        }

        $customer = $this->customerSession->getCustomer();
        $variables['group_id'] = (int) $customer->getGroupId() ?? \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID;
        $variables['id']       = (int) $customer->getId();

        return $variables;
    }
}
