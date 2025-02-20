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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Smile\ElasticsuiteAnalytics\Model\Report\Context as ReportContext;

/**
 * Block used to display customer group selector in reports.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class CustomerGroupSelector extends Template
{
    /**
     * Configuration path for enabling or disabling the Customer group filter.
     *
     * @var string
     */
    const CONFIG_IS_CUSTOMER_GROUP_FILTER_ACTIVE_XPATH = 'smile_elasticsuite_analytics/filters_configuration/customer_group_enabled';

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var CollectionFactory
     */
    protected $customerGroupCollectionFactory;

    /**
     * @var ReportContext
     */
    protected $reportContext;

    /**
     * CustomerGroupSelector constructor.
     *
     * @param Template\Context     $context                        The context of the template.
     * @param ScopeConfigInterface $scopeConfig                    Scope configuration.
     * @param CollectionFactory    $customerGroupCollectionFactory Factory for creating customer group collection.
     * @param ReportContext        $reportContext                  Report context.
     * @param array                $data                           Additional block data.
     */
    public function __construct(
        Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $customerGroupCollectionFactory,
        ReportContext $reportContext,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
        $this->reportContext = $reportContext;
        parent::__construct($context, $data);
    }

    /**
     * Check if the Customer group filter should be displayed.
     *
     * @return bool
     */
    public function isCustomerGroupFilterEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_IS_CUSTOMER_GROUP_FILTER_ACTIVE_XPATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get customer groups in an option array format.
     *
     * @return array
     */
    public function getCustomerGroups()
    {
        return $this->customerGroupCollectionFactory->create()->toOptionArray();
    }

    /**
     * Get customer group ID.
     *
     * @return mixed
     */
    public function getCurrentCustomerGroupId()
    {
        return $this->reportContext->getCustomerGroupId();
    }
}
