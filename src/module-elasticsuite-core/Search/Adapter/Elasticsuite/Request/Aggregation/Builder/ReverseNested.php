<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\BuilderInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Reverse nested aggregation builder.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ReverseNested implements BuilderInterface
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
        if ($bucket->getType() !== BucketInterface::TYPE_REVERSE_NESTED) {
            throw new \InvalidArgumentException("Query builder : invalid aggregation type {$bucket->getType()}.");
        }

        return ['reverse_nested' => new \StdClass()];
    }
}
