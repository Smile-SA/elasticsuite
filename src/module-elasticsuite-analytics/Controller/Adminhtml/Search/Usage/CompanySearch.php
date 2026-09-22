<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Controller\Adminhtml\Search\Usage;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DB\Helper as DbHelper;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Store\Model\ScopeInterface;

/**
 * Ajax endpoint returning a paginated, name-filtered list of companies for the search usage dashboard's
 * company filter field.
 * Response shape ({options: [{value, label}], total}) matches Magento_Ui/js/form/element/ui-select.js's
 * native contract.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class CompanySearch extends Action implements HttpGetActionInterface
{
    /**
     * Base ACL resource for this endpoint. Also requires Magento_Company::index, see _isAllowed().
     *
     * @var string
     */
    const ADMIN_RESOURCE = 'Smile_ElasticsuiteAnalytics::search_usage';

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
     * Default/maximum page size.
     *
     * @var int
     */
    const DEFAULT_PAGE_SIZE = 20;
    const MAX_PAGE_SIZE     = 100;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var DbHelper
     */
    private $dbHelper;

    /**
     * @var \Magento\Company\Api\CompanyRepositoryInterface|null
     */
    private $companyRepository = null;

    /**
     * Constructor.
     *
     * @param Context               $context               Context.
     * @param JsonFactory           $resultJsonFactory     Json result factory.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder Search criteria builder.
     * @param SortOrderBuilder      $sortOrderBuilder      Sort order builder.
     * @param ScopeConfigInterface  $scopeConfig           Scope configuration.
     * @param ModuleManager         $moduleManager         Module manager.
     * @param DbHelper              $dbHelper              DB helper, used to escape the like filter value.
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        ScopeConfigInterface $scopeConfig,
        ModuleManager $moduleManager,
        DbHelper $dbHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->moduleManager = $moduleManager;
        $this->dbHelper = $dbHelper;

        if ($this->moduleManager->isEnabled('Magento_Company')
            && interface_exists('\Magento\Company\Api\CompanyRepositoryInterface')
        ) {
            $this->companyRepository = ObjectManager::getInstance()->get(
                \Magento\Company\Api\CompanyRepositoryInterface::class
            );
        }
    }

    /**
     * Return a paginated, name-filtered list of companies as JSON.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $result->setData($this->getOptions());

        return $result;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * Requires both this module's own ACL resource and Magento_Company::index (the resource guarding
     * the core Companies grid), so a user without company-management rights cannot enumerate company
     * names through this endpoint.
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE)
            && $this->_authorization->isAllowed('Magento_Company::index');
    }

    /**
     * Build the {options, total} payload.
     *
     * @return array
     */
    private function getOptions()
    {
        if (!$this->isCompanyFilterAvailable()) {
            return ['options' => [], 'total' => 0];
        }

        $searchKey = (string) $this->getRequest()->getParam('searchKey', '');
        $page      = max(1, (int) $this->getRequest()->getParam('page', 1));
        $limit     = max(1, min(self::MAX_PAGE_SIZE, (int) $this->getRequest()->getParam('limit', self::DEFAULT_PAGE_SIZE)));

        $sortOrder = $this->sortOrderBuilder
            ->setField('company_name')
            ->setDirection(SortOrder::SORT_ASC)
            ->create();

        $this->searchCriteriaBuilder
            ->addSortOrder($sortOrder)
            ->setPageSize($limit)
            ->setCurrentPage($page);

        if ($searchKey !== '') {
            $escapedSearchKey = $this->dbHelper->escapeLikeValue($searchKey, ['position' => 'any']);
            $this->searchCriteriaBuilder->addFilter('company_name', $escapedSearchKey, 'like');
        }

        $searchResult = $this->companyRepository->getList($this->searchCriteriaBuilder->create());

        $options = [];
        foreach ($searchResult->getItems() as $company) {
            $options[] = [
                'value' => $company->getId(),
                'label' => $company->getCompanyName(),
            ];
        }

        return [
            'options' => $options,
            'total'   => (int) $searchResult->getTotalCount(),
        ];
    }

    /**
     * Check whether the company filter can be served: Magento_Company enabled, B2B company feature
     * enabled, and this module's own company filter toggle enabled.
     *
     * @return bool
     */
    private function isCompanyFilterAvailable()
    {
        return $this->companyRepository !== null
            && $this->scopeConfig->isSetFlag(self::CONFIG_IS_B2B_COMPANY_ACTIVE_XPATH, ScopeInterface::SCOPE_STORE)
            && $this->scopeConfig->isSetFlag(self::CONFIG_IS_COMPANY_FILTER_ACTIVE_XPATH, ScopeInterface::SCOPE_STORE);
    }
}
