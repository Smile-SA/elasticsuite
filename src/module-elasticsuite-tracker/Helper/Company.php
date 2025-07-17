<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager;

/**
 * Company helper.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class Company extends AbstractHelper
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var \Magento\Company\Api\CompanyManagementInterface|null
     */
    private $companyManagement = null;

    /**
     * Constructor.
     *
     * @param Session $customerSession Customer session.
     * @param Manager $moduleManager   Module manager.
     *
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function __construct(
        Session $customerSession,
        Manager $moduleManager
    ) {
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
     * Retrieve the company ID from the customer session.
     *
     * If the customer has an associated company, return the company ID, otherwise return null if no company is assigned.
     *
     * @return int|null
     */
    public function getCompanyId(): ?int
    {
        if ($this->customerSession->isLoggedIn() && (null !== $this->companyManagement)) {
            try {
                $customer = $this->customerSession->getCustomer();
                $company = $this->companyManagement->getByCustomerId($customer->getId());

                return $company ? $company->getId() : null;
            } catch (\Exception $e) {
                return null;
            }
        }

        // Return null if the user is non-logged-in or companyManagement is not available.
        return null;
    }
}
