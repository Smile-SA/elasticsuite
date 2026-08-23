<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\Spellcheck;

use Smile\ElasticsuiteAnalytics\Model\Report\AggregationProviderInterface;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;

/**
 * Spellcheck usage KPI report aggregation provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytic
 */
class AggregationProvider implements AggregationProviderInterface
{
    /**
     * @var AggregationFactory
     */
    private $aggregationFactory;

    /**
     * AggregationProvider constructor.
     *
     * @param AggregationFactory $aggregationFactory Aggregation factory.
     */
    public function __construct(
        AggregationFactory $aggregationFactory
    ) {
        $this->aggregationFactory = $aggregationFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregation()
    {
        $aggParams = [
            'name'       => 'spellcheck_usage',
            'type'       => BucketInterface::TYPE_METRIC,
            'field'      => 'page.search.is_spellchecked',
            'metricType' => MetricInterface::TYPE_STATS,
        ];

        return $this->aggregationFactory->create(BucketInterface::TYPE_METRIC, $aggParams);
    }
}
