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

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Build an ES histogram aggregation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
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
        $aggParams = ['field' => $bucket->getField(), 'interval' => $bucket->getInterval(), 'min_doc_count' => $bucket->getMinDocCount()];

        return ['histogram' => $aggParams];
    }
}
