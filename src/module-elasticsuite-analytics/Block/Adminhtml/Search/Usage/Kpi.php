<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
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
     * Constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                    $context
     * @param \Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\Report $report
     * @param array                                                      $data
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
     *
     * @return array()
     */
    public function getKpi()
    {
        return $this->report->getData();
    }
}
