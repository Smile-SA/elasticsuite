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

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\BuilderInterface;

/**
 * Build an ES bool query.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Boolean extends AbstractComplexBuilder implements BuilderInterface
{
    const QUERY_CONDITION_MUST   = 'must';
    const QUERY_CONDITION_NOT    = 'must_not';
    const QUERY_CONDITION_SHOULD = 'should';

    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryInterface $query)
    {
        $searchQuery = [];

        $clauses = [
            self::QUERY_CONDITION_MUST,
            self::QUERY_CONDITION_NOT,
            self::QUERY_CONDITION_SHOULD,
        ];

        foreach ($clauses as $clause) {
            $queries = array_map(
                [$this->parentBuilder, 'buildQuery'],
                $this->getQueryClause($query, $clause)
            );
            $searchQuery[$clause] = array_filter($queries);
        }

        $searchQuery['minimum_should_match'] = $query->getMinimumShouldMatch();
        $searchQuery['boost']                = $query->getBoost();

        if ($query->isCached()) {
            $searchQuery['_cache'] = true;
        }

        return ['bool' => $searchQuery];
    }

    /**
     * Return the list of queries associated to a clause.
     *
     * @param QueryInterface $query  Bool query.
     * @param string         $clause Current clause (must, should, must_not).
     *
     * @return QueryInterface[]
     */
    private function getQueryClause($query, $clause)
    {
        $queries = $query->getMust();

        if ($clause == self::QUERY_CONDITION_NOT) {
            $queries = $query->getMustNot();
        } elseif ($clause == self::QUERY_CONDITION_SHOULD) {
            $queries = $query->getShould();
        }

        return $queries;
    }
}
