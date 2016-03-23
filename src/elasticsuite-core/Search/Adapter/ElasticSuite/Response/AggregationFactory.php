<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Response;

/**
 * ElasticSuite aggregations response builder.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AggregationFactory extends \Magento\Framework\Search\Adapter\Mysql\AggregationFactory
{
    /**
     * {@inheritDoc}
     */
    public function create(array $rawAggregation)
    {
        $aggregations = $this->preprocessAggregations($rawAggregation);

        return parent::create($aggregations);
    }

    /**
     * Derefences children aggregations (nested and filter) while they have the same name.
     *
     * @param array $rawAggregation ES Aggregations response.
     *
     * @return array
     */
    private function preprocessAggregations(array $rawAggregation)
    {
        $processedAggregations = [];

        foreach ($rawAggregation as $bucketName => $aggregation) {
            while (isset($aggregation[$bucketName])) {
                $aggregation = $aggregation[$bucketName];
            }

            if (isset($aggregation['buckets'])) {
                foreach ($aggregation['buckets'] as $currentBuket) {
                    $processedAggregations[$bucketName][$currentBuket['key']] = [
                        'value' => $currentBuket['key'],
                        'count' => $currentBuket['doc_count'],
                    ];
                }
            }
        }

        return $processedAggregations;
    }
}
