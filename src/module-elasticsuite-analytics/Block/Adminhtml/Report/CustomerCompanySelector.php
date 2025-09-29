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
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Smile\ElasticsuiteAnalytics\Model\Report\Context as ReportContext;

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
     * Configuration path for enabling or disabling the Company filter.
     *
     * @var string
     */
    const CONFIG_IS_COMPANY_FILTER_ACTIVE_XPATH = 'smile_elasticsuite_analytics/filters_configuration/company_enabled';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ReportContext
     */
    protected $reportContext;

    /**
     * @var \Magento\Company\Api\CompanyRepositoryInterface|null
     */
    private $companyRepository = null;

    /**
     * CustomerCompanySelector constructor.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param Context               $context               Template context.
     * @param ModuleManager         $moduleManager         Module manager.
     * @param ScopeConfigInterface  $scopeConfig           Scope configuration.
     * @param SortOrderBuilder      $sortOrderBuilder      Sort order builder.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder Search criteria builder.
     * @param ReportContext         $reportContext         Report context.
     * @param array                 $data                  Additional block data.
     * @throws LocalizedException
     */
    public function __construct(
        Context $context,
        ModuleManager $moduleManager,
        ScopeConfigInterface $scopeConfig,
        SortOrderBuilder $sortOrderBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ReportContext $reportContext,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->reportContext = $reportContext;

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
     * Check if the Company filter should be displayed.
     *
     * @return bool
     */
    public function isCompanyFilterEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_IS_COMPANY_FILTER_ACTIVE_XPATH,
            ScopeInterface::SCOPE_STORE
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
        if ($this->isCompanyEnabled() && ($this->companyRepository !== null)) {
            $sortOrder = $this->sortOrderBuilder
                ->setField('company_name')
                ->setDirection(SortOrder::SORT_ASC)
                ->create();

            $searchCriteria = $this->searchCriteriaBuilder
                ->addSortOrder($sortOrder)
                ->create();

            return $this->companyRepository->getList($searchCriteria)->getItems(); // Fetch company list.
        }

        return [];
    }

    /**
     * Get customer company ID.
     *
     * @return mixed
     */
    public function getCustomerCompanyId()
    {
        return $this->reportContext->getCustomerCompanyId();
    }
}
