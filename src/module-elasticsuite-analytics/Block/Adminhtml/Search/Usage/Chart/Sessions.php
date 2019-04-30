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
 * Sessions graph block.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage
 */
class Sessions extends \Magento\Backend\Block\Template implements ChartInterface
{
    /**
     * @var \Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\Report
     */
    private $report;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * Constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                    $context    Context.
     * @param \Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\Report $report     KPI report model.
     * @param \Magento\Framework\Serialize\Serializer\Json               $serializer Json serializer.
     * @param array                                                      $data       Data.
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\Report $report,
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
            'colors' => [self::COLOR_BLUE, self::COLOR_RED],
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
                ['type' => 'number', 'label' => __('Count')],
            ],
            'rows' => [],
        ];

        try {
            $reportData = $this->report->getData();
            if (array_key_exists('sessions_count', $reportData)
                && array_key_exists('search_sessions_count', $reportData)
            ) {
                $withSearch     = $reportData['search_sessions_count'];
                $withoutSearch  = $reportData['sessions_count'] - $reportData['search_sessions_count'];

                if ($withoutSearch + $withSearch > 0) {
                    $data['rows'] = [
                        [
                            'c' => [
                                ['v' => __('Sessions with search')],
                                ['v' => (int) $withSearch],
                            ],
                        ],
                        [
                            'c' => [
                                ['v' => __('Sessions without search')],
                                ['v' => (int) $withoutSearch],
                            ],
                        ],
                    ];
                }
            }
        } catch (\LogicException $e) {
            ;
        }

        return $this->serializer->serialize($data);
    }
}
