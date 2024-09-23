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
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Additional customer data tracking
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
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;

    /**
     * @var \Magento\Company\Api\CompanyRepositoryInterface|null
     */
    private $companyRepository = null;

    /**
     * @param CustomerSession $customerSession Customer session
     */
    public function __construct(CustomerSession $customerSession/*, ModuleManager $moduleManager, ObjectManager $objectManager*/)
    {
        $this->customerSession = $customerSession;
        //$this->moduleManager   = $moduleManager;
        // Check if Magento_Company module is enabled before attempting to load the repository.
        /*if ($this->moduleManager->isEnabled('Magento_Company')) {
            if (interface_exists('\Magento\Company\Api\CompanyRepositoryInterface')) {
                $this->companyRepository = $objectManager->get(\Magento\Company\Api\CompanyRepositoryInterface::class);
            } else {
                throw new LocalizedException(__('CompanyRepositoryInterface is not available.'));
            }
        }*/
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

        $variables = [
            'age'        => (int) $now->format('Y') - (int) $dob->format('Y'),
            'gender'     => $customer->getGender(),
            'zipcode'    => $shippingAddress ? $shippingAddress->getPostcode() : '',
            'state'      => $shippingAddress ? $shippingAddress->getRegion() : '',
            'country'    => $shippingAddress ? $shippingAddress->getCountry() : '',
            'group_id'   => (int) $customer->getGroupId(),
        ];

        // Check if the customer is logged in and Magento_Company is enabled.
        if ($this->customerSession->isLoggedIn() && (null !== $this->companyRepository)) {
            $customer = $this->customerSession->getCustomer();

            try {
                // Retrieve company information by customer ID.
                $company = $this->companyRepository->getByCustomerId($customer->getId());
                $variables['company_id'] = (int) $company->getId();
            } catch (NoSuchEntityException $e) {
                // No company found for this customer.
            }
        }

        return $variables;
    }
}
