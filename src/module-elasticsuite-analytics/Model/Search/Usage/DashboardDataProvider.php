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

namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage;

use Magento\Backend\Model\UrlInterface as BackendUrl;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage\ChartInterface;
use Smile\ElasticsuiteAnalytics\Model\ReportInterface;

/**
 * Assembles KPI, terms and chart data for the search usage dashboard ajax endpoint.
 * Mirrors the merge/enrichment logic of Block\Adminhtml\Search\Usage\{Kpi,SearchTerms,Chart\*} so the
 * ajax payload matches the full-page render, without depending on the layout/block rendering pipeline.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class DashboardDataProvider
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var BackendUrl
     */
    private $backendUrl;

    /**
     * @var ReportInterface
     */
    private $conversionRatesReport;

    /**
     * @var ReportInterface[]
     */
    private $kpiReports;

    /**
     * @var ReportInterface[]
     */
    private $termsReports;

    /**
     * Constructor.
     *
     * @param QueryFactory      $queryFactory          Search query factory, used to resolve merchandiser edit URLs.
     * @param BackendUrl        $backendUrl            Backend URL builder.
     * @param ReportInterface   $conversionRatesReport Conversion rates report, used by the conversion chart.
     * @param ReportInterface[] $kpiReports            KPI report models to merge into the KPI strip.
     * @param ReportInterface[] $termsReports          Terms report models, keyed by section (popular, spellchecked, no_result).
     */
    public function __construct(
        QueryFactory $queryFactory,
        BackendUrl $backendUrl,
        ReportInterface $conversionRatesReport,
        array $kpiReports = [],
        array $termsReports = []
    ) {
        $this->queryFactory = $queryFactory;
        $this->backendUrl = $backendUrl;
        $this->conversionRatesReport = $conversionRatesReport;
        $this->kpiReports = $kpiReports;
        $this->termsReports = $termsReports;
    }

    /**
     * Get merged KPI data.
     *
     * @return array
     */
    public function getKpi()
    {
        $data = [];

        try {
            foreach ($this->kpiReports as $report) {
                $data += $report->getData();
            }
        } catch (\LogicException $e) {
            ;
        }

        return $data;
    }

    /**
     * Get terms data, keyed by section (popular, spellchecked, no_result).
     *
     * @return array
     */
    public function getTerms()
    {
        $terms = [];

        foreach ($this->termsReports as $key => $report) {
            $terms[$key] = $this->getTermsData($report);
        }

        return $terms;
    }

    /**
     * Get chart data, keyed by section (sessions, spellcheck, conversion).
     *
     * @return array
     */
    public function getCharts()
    {
        $kpi = $this->getKpi();

        return [
            'sessions'   => $this->getSessionsChart($kpi),
            'spellcheck' => $this->getSpellcheckChart($kpi),
            'conversion' => $this->getConversionChart(),
        ];
    }

    /**
     * Get terms data for a single report, enriched with the merchandiser edit URL.
     *
     * @param ReportInterface $report Terms report.
     *
     * @return array
     */
    private function getTermsData(ReportInterface $report)
    {
        $data = [];

        try {
            $data = $report->getData();
        } catch (\LogicException $e) {
            ;
        }

        foreach ($data as &$value) {
            $value['url'] = $this->getMerchandiserUrl($value['term']);
        }

        return array_values($data);
    }

    /**
     * Get the term merchandiser edit URL for a given search term.
     *
     * @param string $term Search term.
     *
     * @return string|null
     */
    private function getMerchandiserUrl($term)
    {
        $url = null;

        $query = $this->queryFactory->create();
        $query->loadByQueryText($term);
        if ($query->getId()) {
            $url = $this->backendUrl->getUrl('search/term/edit', ['id' => $query->getId()]);
        }

        return $url;
    }

    /**
     * Build the "Sessions" pie chart data from the merged KPI data.
     *
     * @param array $kpiData Merged KPI data.
     *
     * @return array
     */
    private function getSessionsChart(array $kpiData)
    {
        $chart = [
            'cols' => [
                ['type' => 'string', 'label' => __('Session type')],
                ['type' => 'number', 'label' => __('Count')],
            ],
            'rows'    => [],
            'options' => ['colors' => [ChartInterface::COLOR_BLUE, ChartInterface::COLOR_RED]],
        ];

        if (array_key_exists('sessions_count', $kpiData) && array_key_exists('search_sessions_count', $kpiData)) {
            $withSearch    = $kpiData['search_sessions_count'];
            $withoutSearch = $kpiData['sessions_count'] - $kpiData['search_sessions_count'];

            if ($withoutSearch + $withSearch > 0) {
                $chart['rows'] = [
                    ['c' => [['v' => __('Sessions with search')], ['v' => (int) $withSearch]]],
                    ['c' => [['v' => __('Sessions without search')], ['v' => (int) $withoutSearch]]],
                ];
            }
        }

        return $chart;
    }

    /**
     * Build the "Spellcheck usage" pie chart data from the merged KPI data.
     *
     * @param array $kpiData Merged KPI data.
     *
     * @return array
     */
    private function getSpellcheckChart(array $kpiData)
    {
        $chart = [
            'cols' => [
                ['type' => 'string', 'label' => __('Search type')],
                ['type' => 'number', 'label' => __('Rate')],
            ],
            'rows'    => [],
            'options' => ['colors' => [ChartInterface::COLOR_RED, ChartInterface::COLOR_BLUE]],
        ];

        if (array_key_exists('spellcheck_usage_rate', $kpiData)) {
            $spellcheckedSearches = $kpiData['spellcheck_usage_rate'];
            $exactSearches        = 1 - $spellcheckedSearches;

            $chart['rows'] = [
                ['c' => [['v' => __('Spellchecked searches')], ['v' => $spellcheckedSearches]]],
                ['c' => [['v' => __('Exact searches')], ['v' => $exactSearches]]],
            ];
        }

        return $chart;
    }

    /**
     * Build the "Conversion rate" bar chart data.
     *
     * @return array
     */
    private function getConversionChart()
    {
        $chart = [
            'cols' => [
                ['type' => 'string', 'label' => __('Session type')],
                ['type' => 'number', 'label' => __('Conversion rate (%)')],
                ['type' => 'string', 'role' => 'style'],
            ],
            'rows'    => [],
            'options' => [
                'animation' => ['startup' => true, 'duration' => 1000, 'easing' => 'out'],
                'hAxis'     => ['baseline' => 0],
                'legend'    => ['position' => 'none'],
                'colors'    => [ChartInterface::COLOR_BLUE],
            ],
        ];

        try {
            $reportData = $this->conversionRatesReport->getData();

            if (array_key_exists('all', $reportData)) {
                $chart['rows'][] = [
                    'c' => [
                        ['v' => __('All sessions')],
                        ['v' => (float) $reportData['all'] * 100.0],
                        ['v' => sprintf('color: %s', ChartInterface::COLOR_GREEN)],
                    ],
                ];
            }

            if (array_key_exists('searches', $reportData)) {
                $chart['rows'][] = [
                    'c' => [
                        ['v' => __('With search')],
                        ['v' => (float) $reportData['searches'] * 100.0],
                        ['v' => sprintf('color: %s', ChartInterface::COLOR_BLUE)],
                    ],
                ];
            }

            if (array_key_exists('no_searches', $reportData)) {
                $chart['rows'][] = [
                    'c' => [
                        ['v' => __('Without search')],
                        ['v' => (float) $reportData['no_searches'] * 100.0],
                        ['v' => sprintf('color: %s', ChartInterface::COLOR_RED)],
                    ],
                ];
            }
        } catch (\LogicException $e) {
            ;
        }

        return $chart;
    }
}
