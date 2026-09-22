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
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Smile\ElasticsuiteAnalytics\Model\Search\Usage\DashboardDataProvider;

/**
 * Ajax endpoint returning the search usage dashboard's KPI, terms and chart data as JSON.
 * Accepts the exact same request params as Search\Usage (store, customer_group, company_id, from, to),
 * read identically by Model\Report\Context, so a filter change reaches the same data whether it comes
 * from a full page load or this ajax endpoint.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class Data extends Action implements HttpGetActionInterface
{
    /**
     * @var string
     */
    const ADMIN_RESOURCE = 'Smile_ElasticsuiteAnalytics::search_usage';

    /**
     * @var DashboardDataProvider
     */
    private $dashboardDataProvider;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Constructor.
     *
     * @param Context               $context               Context.
     * @param DashboardDataProvider $dashboardDataProvider Dashboard data provider.
     * @param JsonFactory           $resultJsonFactory     Json result factory.
     */
    public function __construct(
        Context $context,
        DashboardDataProvider $dashboardDataProvider,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->dashboardDataProvider = $dashboardDataProvider;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Return the dashboard's KPI, terms and chart data as JSON.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $result->setData([
            'kpi'    => $this->dashboardDataProvider->getKpi(),
            'terms'  => $this->dashboardDataProvider->getTerms(),
            'charts' => $this->dashboardDataProvider->getCharts(),
        ]);

        return $result;
    }
}
