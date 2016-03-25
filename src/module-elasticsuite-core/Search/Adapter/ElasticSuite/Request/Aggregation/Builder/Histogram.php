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

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Aggregation\Builder;

use Smile\ElasticSuiteCore\Search\Request\BucketInterface;

/**
 * Build an ES histogram aggregation.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Histogram
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
        return ['histogram' => ['field' => $bucket->getField(), 'interval' => $bucket->getInterval()]];
    }
}
