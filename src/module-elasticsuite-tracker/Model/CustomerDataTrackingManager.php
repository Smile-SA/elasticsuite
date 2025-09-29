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

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager as ModuleManager;

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
     * @var \Magento\Company\Api\CompanyManagementInterface|null
     */
    private $companyManagement = null;

    /**
     * CustomerDataTrackingManager constructor.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param CustomerSession $customerSession Customer session.
     * @param ModuleManager   $moduleManager   Module manager.
     * @throws LocalizedException
     */
    public function __construct(CustomerSession $customerSession, ModuleManager $moduleManager)
    {
        $this->customerSession = $customerSession;
        // Check if Magento_Company module is enabled before attempting to load the repository.
        if ($moduleManager->isEnabled('Magento_Company')) {
            if (interface_exists('\Magento\Company\Api\CompanyManagementInterface')) {
                $this->companyManagement = ObjectManager::getInstance()->get(
                    \Magento\Company\Api\CompanyManagementInterface::class
                );
            } else {
                throw new LocalizedException(__('CompanyManagementInterface is not available.'));
            }
        }
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
        $variables['group_id'] = (int) ($customer->getGroupId() ?? \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
        $variables['id']       = (int) $customer->getId();

        if ($this->customerSession->isLoggedIn() && (null !== $this->companyManagement)) {
            try {
                // Use CompanyUserManager to retrieve company information by customer ID.
                $company = $this->companyManagement->getByCustomerId($customer->getId());

                // If company is found, add the company ID to the variables array.
                if ($company) {
                    $variables['company_id'] = (int) $company->getId();
                }
            } catch (NoSuchEntityException $e) {
                // No company found for this customer.
            }
        }

        return $variables;
    }
}
