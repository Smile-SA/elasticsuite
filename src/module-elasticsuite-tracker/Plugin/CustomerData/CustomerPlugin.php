<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Pierre Le Maguer <pierre.lemaguer@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Plugin\CustomerData;

use Magento\Customer\CustomerData\Customer;
use Smile\ElasticsuiteTracker\Model\CustomerDataTrackingManager;

/**
 * Add customer info in customer data.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Pierre Le Maguer <pierre.lemaguer@smile.fr>
 */
class CustomerPlugin
{
    /** @var CustomerDataTrackingManager */
    protected $customerDataTrackingManager;

    public function __construct(
        CustomerDataTrackingManager $customerDataTrackingManager
    ) {
        $this->customerDataTrackingManager = $customerDataTrackingManager;
    }

    /**
     * Add customer data.
     *
     * @param Customer $subject Subject
     * @param array    $result  Result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(Customer $subject, array $result): array
    {
        $result['tracking'] = $this->customerDataTrackingManager->getCustomerDataToTrack();

        return $result;
    }
}
