<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\BuilderInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;

/**
 * Top Hits aggregation builder.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class TopHits implements BuilderInterface
{
    /**
     * Build the aggregation.
     *
     * @param BucketInterface $bucket Top Hits Bucket.
     *
     * @return array
     */
    public function buildBucket(BucketInterface $bucket)
    {
        if ($bucket->getType() !== BucketInterface::TYPE_TOP_HITS) {
            throw new \InvalidArgumentException("Query builder : invalid aggregation type {$bucket->getType()}.");
        }

        $params = [];

        if (!empty($bucket->getSource())) {
            $params['_source']['includes'] = $bucket->getSource();
        }

        if ($bucket->getSize() && ($bucket->getSize() > 0)) {
            $params['size'] = $bucket->getSize();
        }

        if (is_array($bucket->getSortOrder())) {
            $params['sort'] = $bucket->getSortOrder();
        } elseif ($bucket->getSortOrder() == $bucket::SORT_ORDER_RELEVANCE && !$bucket->isNested()) {
            $params['sort'] = [$bucket::SORT_ORDER_RELEVANCE => SortOrderInterface::SORT_DESC];
        }

        return ['top_hits' => $params];
    }
}
