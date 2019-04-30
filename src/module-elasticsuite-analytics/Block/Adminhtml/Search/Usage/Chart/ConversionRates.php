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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage\Chart;

use Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage\ChartInterface;

/**
 * ConversionRates graph block.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class ConversionRates extends \Magento\Backend\Block\Template implements ChartInterface
{
    /**
     * @var \Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\ConversionRates\Report
     */
    private $report;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * Constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                                    $context    Context.
     * @param \Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\ConversionRates\Report $report     Report model.
     * @param \Magento\Framework\Serialize\Serializer\Json                               $serializer Json serializer.
     * @param array                                                                      $data       Data.
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\ConversionRates\Report $report,
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
            'animation' => [
                'startup' => true,
                'duration' => 1000,
                'easing'   => 'out',
            ],
            'hAxis' => ['baseline' => 0],
            'legend' => ['position' => 'none'],
            'colors' => [self::COLOR_BLUE],
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
                ['type' => 'string', 'label' => __('Session type')],
                ['type' => 'number', 'label' => __('Conversion rate (%)')],
                ['type' => 'string', 'role' => 'style'],
            ],
            'rows' => [],
        ];

        try {
            $reportData = $this->report->getData();

            if (array_key_exists('all', $reportData)) {
                $data['rows'][] = [
                    'c' => [
                        ['v' => __('All sessions')],
                        ['v' => (float) $reportData['all'] * 100.0],
                        ['v' => sprintf('color: %s', self::COLOR_GREEN)],
                    ],
                ];
            }

            if (array_key_exists('searches', $reportData)) {
                $data['rows'][] = [
                    'c' => [
                        ['v' => __('With search')],
                        ['v' => (float) $reportData['searches'] * 100.0],
                        ['v' => sprintf('color: %s', self::COLOR_BLUE)],
                    ],
                ];
            }

            if (array_key_exists('no_searches', $reportData)) {
                $data['rows'][] = [
                    'c' => [
                        ['v' => __('Without search')],
                        ['v' => (float) $reportData['no_searches'] * 100.0],
                        ['v' => sprintf('color: %s', self::COLOR_RED)],
                    ],
                ];
            }
        } catch (\LogicException $e) {
            ;
        }

        return $this->serializer->serialize($data);
    }
}
