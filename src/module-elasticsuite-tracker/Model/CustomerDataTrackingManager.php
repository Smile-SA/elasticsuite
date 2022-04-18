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
        if (!$this->customerSession->getId()) {
            return [];
        }

        $customer = $this->customerSession->getCustomer();
        $shippingAddress = $customer->getDefaultShippingAddress();

        $dob = new DateTime($customer->getDob() ?? '');
        $now = new DateTime();

        return [
            'age' => (int) $now->format('Y') - (int) $dob->format('Y'),
            'gender' => $customer->getGender(),
            'zipcode' => $shippingAddress ? $shippingAddress->getPostcode() : '',
            'state' => $shippingAddress ? $shippingAddress->getRegion() : '',
            'country' => $shippingAddress ? $shippingAddress->getCountry() : '',
        ];
    }
}
