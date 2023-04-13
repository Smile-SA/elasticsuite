<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Span;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\BuilderInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Build an ES span_term query.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SpanTerm implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryInterface $query)
    {
        if ($query->getType() !== SpanQueryInterface::TYPE_SPAN_TERM) {
            throw new \InvalidArgumentException("Query builder : invalid query type {$query->getType()}");
        }

        $searchQueryParams = [
            'value' => $query->getValue(),
            'boost' => $query->getBoost(),
        ];

        $searchQuery = ['span_term' => [$query->getField() => $searchQueryParams]];

        if ($query->getName()) {
            $searchQuery['span_term']['_name'] = $query->getName();
        }

        return $searchQuery;
    }
}
