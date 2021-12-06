<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\BuilderInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Build a top-level ES metric aggregation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class Metric implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function buildBucket(BucketInterface $bucket)
    {
        if ($bucket->getType() !== BucketInterface::TYPE_METRIC) {
            throw new \InvalidArgumentException("Query builder : invalid aggregation type {$bucket->getType()}.");
        }

        $metricDefinition = array_merge(['field' => $bucket->getField()], $bucket->getConfig() ?? []);
        if (isset($metricDefinition['script'])) {
            unset($metricDefinition['field']);
        }

        return [$bucket->getMetricType() => $metricDefinition];
    }
}
