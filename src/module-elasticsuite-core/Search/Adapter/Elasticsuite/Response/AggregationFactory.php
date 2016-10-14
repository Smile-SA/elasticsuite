<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response;

/**
 * ElasticSuite aggregations response builder.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
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
                foreach ($aggregation['buckets'] as $key => $currentBuket) {
                    if (isset($currentBuket['key'])) {
                        $key = $currentBuket['key'];
                    }

                    $processedAggregations[$bucketName][$key] = [
                        'value' => $key,
                        'count' => $currentBuket['doc_count'],
                    ];
                }
            }
            if (isset($aggregation['sum_other_doc_count'])) {
                $processedAggregations[$bucketName]['__other_docs'] = [
                    'value' => '__other_docs',
                    'count' => $aggregation['sum_other_doc_count'],
                ];
            }
        }

        return $processedAggregations;
    }
}
