<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\PipelineBuilder;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\PipelineBuilderInterface;
use Smile\ElasticsuiteCore\Search\Request\PipelineInterface;

/**
 * Build a bucket selector ES pipeline aggregation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class BucketSelector implements PipelineBuilderInterface
{
    /**
     * Build the pipeline aggregation.
     *
     * @param PipelineInterface $pipeline Bucket selector pipeline.
     *
     * @return array
     */
    public function buildPipeline(PipelineInterface $pipeline)
    {
        if ($pipeline->getType() !== PipelineInterface::TYPE_BUCKET_SELECTOR) {
            throw new \InvalidArgumentException("Query builder : invalid aggregation type {$pipeline->getType()}.");
        }

        $aggParams = [
            'buckets_path'  => $pipeline->getBucketsPath(),
            'script'        => $pipeline->getScript(),
            'gap_policy'    => $pipeline->getGapPolicy(),
        ];

        return ['bucket_selector' => $aggParams];
    }
}
