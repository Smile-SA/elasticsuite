<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage;

/**
 * Block used to display KPIs in the search usage dashboard.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Kpi extends \Magento\Backend\Block\Template
{
    /**
     * @var \Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\Report
     */
    private $report;

    /**
     * Constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                    $context Context.
     * @param \Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\Report $report  Report model.
     * @param array                                                      $data    Data.
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\Report $report,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->report = $report;
    }

    /**
     * Get report data
     *
     * @return array()
     */
    public function getKpi()
    {
        $data = [];

        try {
            $data = $this->report->getData();
        } catch (\LogicException $e) {
            ;
        }

        return $data;
    }
}
