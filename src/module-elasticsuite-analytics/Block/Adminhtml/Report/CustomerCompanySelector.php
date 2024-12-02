<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Block\Adminhtml\Report;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Block used to display customer company selector in reports.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class CustomerCompanySelector extends Template
{
    /**
     * Company status configuration path.
     *
     * @var string
     */
    const CONFIG_IS_B2B_COMPANY_ACTIVE_XPATH = 'btob/website_configuration/company_active';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Company\Api\CompanyRepositoryInterface|null
     */
    private $companyRepository = null;

    /**
     * CustomerCompanySelector constructor.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param Context               $context               The template context.
     * @param ModuleManager         $moduleManager         Module manager.
     * @param ScopeConfigInterface  $scopeConfig           Scope configuration.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder The search criteria builder.
     * @param array                 $data                  Additional block data.
     * @throws LocalizedException
     */
    public function __construct(
        Context $context,
        ModuleManager $moduleManager,
        ScopeConfigInterface $scopeConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;

        // Check if Magento_Company module is enabled before attempting to load the repository.
        if ($moduleManager->isEnabled('Magento_Company')) {
            if (interface_exists('\Magento\Company\Api\CompanyRepositoryInterface')) {
                $this->companyRepository = ObjectManager::getInstance()->get(
                    \Magento\Company\Api\CompanyRepositoryInterface::class
                );
            } else {
                throw new LocalizedException(__('CompanyRepositoryInterface is not available.'));
            }
        }

        parent::__construct($context, $data);
    }

    /**
     * Check if the Company feature is enabled.
     *
     * @return bool
     */
    public function isCompanyEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_IS_B2B_COMPANY_ACTIVE_XPATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get the list of companies if the Company feature is enabled.
     *
     * @return CompanyInterface[]|array
     * @throws LocalizedException
     */
    public function getCompaniesList()
    {
        if ($this->isCompanyEnabled()) {
            $searchCriteria = $this->searchCriteriaBuilder->create();

            return $this->companyRepository->getList($searchCriteria)->getItems(); // Fetch company list.
        }

        return [];
    }
}
