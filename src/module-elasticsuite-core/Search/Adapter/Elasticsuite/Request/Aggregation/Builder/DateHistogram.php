<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\BuilderInterface;

/**
 * Build an ES date histogram aggregation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class DateHistogram implements BuilderInterface
{
    /**
     * Build the aggregation.
     *
     * @param BucketInterface $bucket Histogram bucket.
     *
     * @return array
     */
    public function buildBucket(BucketInterface $bucket)
    {
        if ($bucket->getType() !== BucketInterface::TYPE_DATE_HISTOGRAM) {
            throw new \InvalidArgumentException("Query builder : invalid aggregation type {$bucket->getType()}.");
        }

        $aggParams = [
            'field'         => $bucket->getField(),
            'interval'      => $bucket->getInterval(),
            'min_doc_count' => $bucket->getMinDocCount(),
        ];

        return ['date_histogram' => $aggParams];
    }
}
