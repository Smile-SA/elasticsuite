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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage\Chart;

use Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage\ChartInterface;

/**
 * Spellchecked vs non-spellchecked queries graph block.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage
 */
class Spellcheck extends \Magento\Backend\Block\Template implements ChartInterface
{
    /**
     * @var \Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\Spellcheck\Report
     */
    private $report;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * Constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                               $context    Context.
     * @param \Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\Spellcheck\Report $report     KPI report model.
     * @param \Magento\Framework\Serialize\Serializer\Json                          $serializer Json serializer.
     * @param array                                                                 $data       Data.
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\Spellcheck\Report $report,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->report = $report;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function getChartOptions()
    {
        $options = [
            'colors' => [self::COLOR_RED, self::COLOR_BLUE],
        ];

        return $this->serializer->serialize($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getChartData()
    {
        $data = [
            'cols' => [
                ['type' => 'string', 'label' => __('Search type')],
                ['type' => 'number', 'label' => __('Rate')],
            ],
            'rows' => [],
        ];

        try {
            $reportData = $this->report->getData();
            if (array_key_exists('spellcheck_usage_rate', $reportData)) {
                $spellcheckedSearches = $reportData['spellcheck_usage_rate'];
                $exactSearches        = 1 - $spellcheckedSearches;

                $data['rows'] = [
                    [
                        'c' => [
                            ['v' => __('Spellchecked searches')],
                            ['v' => $spellcheckedSearches],
                        ],
                    ],
                    [
                        'c' => [
                            ['v' => __('Exact searches')],
                            ['v' => $exactSearches],
                        ],
                    ],
                ];
            }
        } catch (\LogicException $e) {
            ;
        }

        return $this->serializer->serialize($data);
    }
}
