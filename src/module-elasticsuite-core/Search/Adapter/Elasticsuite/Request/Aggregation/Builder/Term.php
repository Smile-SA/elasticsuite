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
use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;

/**
 * Build an ES Term aggregation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Term
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
        $aggregation = ['terms' => ['field' => $bucket->getField(), 'size' => $bucket->getSize()]];

        if (in_array($bucket->getSortOrder(), [$bucket::SORT_ORDER_COUNT, $bucket::SORT_ORDER_MANUAL])) {
            $aggregation['terms']['order'] = [$bucket::SORT_ORDER_COUNT => SortOrderInterface::SORT_DESC];
        } elseif ($bucket->getSortOrder() == $bucket::SORT_ORDER_TERM) {
            $aggregation['terms']['order'] = [$bucket::SORT_ORDER_TERM => SortOrderInterface::SORT_ASC];
        } elseif ($bucket->getSortOrder() == $bucket::SORT_ORDER_RELEVANCE && !$bucket->isNested()) {
            $aggregation['aggregations']['termRelevance'] = ['avg' => ['script' => $bucket::SORT_ORDER_RELEVANCE]];
            $aggregation['terms']['order'] = ['termRelevance' => SortOrderInterface::SORT_DESC];
        }

        return $aggregation;
    }
}
