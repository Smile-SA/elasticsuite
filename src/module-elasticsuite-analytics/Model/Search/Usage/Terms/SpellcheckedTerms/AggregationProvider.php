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

namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage\Terms\SpellcheckedTerms;

use Smile\ElasticsuiteAnalytics\Model\Search\Usage\Terms\AggregationProvider as TermsAggregationProvider;
use Smile\ElasticsuiteCore\Search\Request\PipelineInterface;

/**
 * Aggregation provider for spellcheck terms, excluding those that return 0 results.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class AggregationProvider extends TermsAggregationProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getPipelines()
    {
        $pipelines = [
            $this->pipelineFactory->create(
                PipelineInterface::TYPE_BUCKET_SELECTOR,
                [
                    'name' => 'result_count_filter',
                    'bucketsPath' => ['avg_result_count' => 'result_count.result_count'],
                    'script' => 'params.avg_result_count > 0',
                ]
            ),
        ];

        return $pipelines;
    }
}
