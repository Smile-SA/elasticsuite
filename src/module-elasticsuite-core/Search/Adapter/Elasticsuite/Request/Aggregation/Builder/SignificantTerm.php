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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\BuilderInterface;

/**
 * Build an ES significant term aggregation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class SignificantTerm implements BuilderInterface
{
    /**
     * Build the aggregation.
     *
     * @param BucketInterface $bucket Term bucket.
     *
     * @return array
     */
    public function buildBucket(BucketInterface $bucket)
    {
        if ($bucket->getType() !== BucketInterface::TYPE_SIGNIFICANT_TERM) {
            throw new \InvalidArgumentException("Query builder : invalid aggregation type {$bucket->getType()}.");
        }
        $aggrepationParams = [
            'field'                 => $bucket->getField(),
            'size'                  => $bucket->getSize(),
            'min_doc_count'         => $bucket->getMinDocCount(),
            $bucket->getAlgorithm() => new \stdClass(),
        ];

        return ['significant_terms' => $aggrepationParams];
    }
}
