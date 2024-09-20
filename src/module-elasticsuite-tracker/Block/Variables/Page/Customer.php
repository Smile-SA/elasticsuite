<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteTracker\Block\Variables\Page;

use Magento\Customer\Model\Group as CustomerGroup;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Smile\ElasticsuiteTracker\Helper\Data as TrackerHelper;

/**
 * Customer variables block for page tracking.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class Customer extends \Smile\ElasticsuiteTracker\Block\Variables\Page\AbstractBlock
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var \Magento\Company\Api\CompanyRepositoryInterface|null
     */
    private $companyRepository = null;

    /**
     * Customer constructor.
     *
     * @param Template\Context                $context           Template Context.
     * @param Data                            $jsonHelper        Magento JSON Helper.
     * @param TrackerHelper                   $trackerHelper     Smile Tracker Helper.
     * @param Registry                        $registry          Magento Core Registry.
     * @param Session                         $customerSession   Customer Session.
     * @param ModuleManager                   $moduleManager     Magento Module Manager.
     * @param array                           $data              Additional data.
     * @throws LocalizedException
     */
    public function __construct(
        Template\Context $context,
        Data $jsonHelper,
        TrackerHelper $trackerHelper,
        Registry $registry,
        Session $customerSession,
        ModuleManager $moduleManager,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->moduleManager = $moduleManager;

        // Check if Magento_Company module is enabled before attempting to load the repository.
        if ($this->moduleManager->isEnabled('Magento_Company')) {
            if (interface_exists('\Magento\Company\Api\CompanyRepositoryInterface')) {
                $this->companyRepository = $context->getObjectManager()->get(\Magento\Company\Api\CompanyRepositoryInterface::class);
            } else {
                throw new LocalizedException(__('CompanyRepositoryInterface is not available.'));
            }
        }

        parent::__construct($context, $jsonHelper, $trackerHelper, $registry, $data);
    }

    /**
     * Returns an array of customer-related variables (customer_group_id and company_id).
     *
     * @return array
     */
    public function getVariables()
    {
        return array_merge(
            $this->getCustomerGroupId(),
            $this->getCustomerCompanyId()
        );
    }

    /**
     * Retrieve customer group ID.
     * If the customer is logged in, fetch their group ID.
     * Otherwise, assign the 'NOT LOGGED IN' group ID.
     *
     * @return array
     */
    private function getCustomerGroupId()
    {
        $variables = [];

        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerSession->getCustomer();
            $variables['customer.group_id'] = $customer->getGroupId();
        } else {
            // Guest user (NOT LOGGED IN).
            $variables['customer.group_id'] = CustomerGroup::NOT_LOGGED_IN_ID;
        }

        return $variables;
    }

    /**
     * Retrieve customer company ID.
     * If the customer is logged in and Magento_Company module is enabled, fetch the company ID.
     * Otherwise, return null for guests or when the module is not available.
     *
     * @return array
     */
    private function getCustomerCompanyId()
    {
        $variables = [];

        // Check if the customer is logged in and Magento_Company is enabled.
        if ($this->customerSession->isLoggedIn() && $this->companyRepository) {
            $customer = $this->customerSession->getCustomer();

            try {
                // Retrieve company information by customer ID.
                $company = $this->companyRepository->getByCustomerId($customer->getId());
                $variables['customer.company_id'] = $company->getId();
            } catch (NoSuchEntityException $e) {
                // No company found for this customer.
                $variables['customer.company_id'] = null;
            }
        } else {
            // No company for guests or when the module is not available.
            $variables['customer.company_id'] = null;
        }

        return $variables;
    }
}
