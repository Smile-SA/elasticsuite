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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\AbstractComplexBuilder;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\BuilderInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Build an ES span_not query.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SpanNot extends AbstractComplexBuilder implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryInterface $query)
    {
        if ($query->getType() !== SpanQueryInterface::TYPE_SPAN_NOT) {
            throw new \InvalidArgumentException("Query builder : invalid query type {$query->getType()}");
        }

        return [
            'span_not' => [
                'boost'   => $query->getBoost(),
                'include' => $this->parentBuilder->buildQuery($query->getInclude()),
                'exclude' => $this->parentBuilder->buildQuery($query->getExclude()),
            ],
        ];
    }
}
