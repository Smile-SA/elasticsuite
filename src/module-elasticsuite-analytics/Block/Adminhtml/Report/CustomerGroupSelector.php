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

use Magento\Framework\View\Element\Template;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
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
     * @param Template\Context  $context                        The context of the template.
     * @param CollectionFactory $customerGroupCollectionFactory Factory for creating customer group collection.
     * @param ReportContext     $reportContext                  Report context.
     * @param array             $data                           Additional block data.
     */
    public function __construct(
        Template\Context $context,
        CollectionFactory $customerGroupCollectionFactory,
        ReportContext $reportContext,
        array $data = []
    ) {
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
        $this->reportContext = $reportContext;
        parent::__construct($context, $data);
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
