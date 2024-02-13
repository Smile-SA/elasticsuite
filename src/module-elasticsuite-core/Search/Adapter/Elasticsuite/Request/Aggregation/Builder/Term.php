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
use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\BuilderInterface;

/**
 * Build an ES Term aggregation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Term implements BuilderInterface
{
    /**
     * Build the aggregation.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param BucketInterface $bucket Term bucket.
     *
     * @return array
     */
    public function buildBucket(BucketInterface $bucket)
    {
        if ($bucket->getType() !== BucketInterface::TYPE_TERM) {
            throw new \InvalidArgumentException("Query builder : invalid aggregation type {$bucket->getType()}.");
        }

        $aggregation = ['terms' => ['field' => $bucket->getField(), 'size' => $bucket->getSize()]];

        if (is_array($bucket->getSortOrder())) {
            $aggregation['terms']['order'] = $bucket->getSortOrder();
        } elseif (in_array($bucket->getSortOrder(), [$bucket::SORT_ORDER_COUNT, $bucket::SORT_ORDER_MANUAL])) {
            $aggregation['terms']['order'] = [$bucket::SORT_ORDER_COUNT => SortOrderInterface::SORT_DESC];
        } elseif (in_array($bucket->getSortOrder(), [$bucket::SORT_ORDER_TERM, $bucket::SORT_ORDER_TERM_DEPRECATED])) {
            $aggregation['terms']['order'] = [$bucket::SORT_ORDER_TERM => SortOrderInterface::SORT_ASC];
        } elseif ($bucket->getSortOrder() == $bucket::SORT_ORDER_RELEVANCE && !$bucket->isNested()) {
            $aggregation['aggregations']['termRelevance'] = ['avg' => ['script' => $bucket::SORT_ORDER_RELEVANCE]];
            $aggregation['terms']['order'] = ['termRelevance' => SortOrderInterface::SORT_DESC];
        }

        if (!empty($bucket->getInclude())) {
            $aggregation['terms']['include'] = $bucket->getInclude();
        }
        if (!empty($bucket->getExclude())) {
            $aggregation['terms']['exclude'] = $bucket->getExclude();
        }
        if ($bucket->getMinDocCount() !== null) {
            $aggregation['terms']['min_doc_count'] = $bucket->getMinDocCount();
        }

        return $aggregation;
    }
}
