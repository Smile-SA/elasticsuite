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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage;

use Magento\Backend\Block\Template\Context;
use Smile\ElasticsuiteAnalytics\Model\ReportInterface;

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
     * @var ReportInterface[]
     */
    private $reports = [];

    /**
     * Constructor.
     *
     * @param Context           $context Context.
     * @param ReportInterface[] $reports Report model whose results will be aggregated.
     * @param array             $data    Data.
     */
    public function __construct(
        Context $context,
        array $reports = [],
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->reports = $reports;
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
            foreach ($this->reports as $report) {
                $data += $report->getData();
            }
        } catch (\LogicException $e) {
            ;
        }

        return $data;
    }
}
