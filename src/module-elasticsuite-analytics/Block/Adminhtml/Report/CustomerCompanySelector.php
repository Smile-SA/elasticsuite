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

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

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
     * @var CompanyRepositoryInterface
     */
    protected $companyRepository;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * CustomerCompanySelector constructor.
     *
     * @param Context                    $context               The template context.
     * @param CompanyRepositoryInterface $companyRepository     The company repository.
     * @param ScopeConfigInterface       $scopeConfig           Scope configuration.
     * @param SearchCriteriaBuilder      $searchCriteriaBuilder The search criteria builder.
     * @param array                      $data                  Additional block data.
     */
    public function __construct(
        Context $context,
        CompanyRepositoryInterface $companyRepository,
        ScopeConfigInterface $scopeConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        $this->companyRepository = $companyRepository;
        $this->scopeConfig = $scopeConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
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
