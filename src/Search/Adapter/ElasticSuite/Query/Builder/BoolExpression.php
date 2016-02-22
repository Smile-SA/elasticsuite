<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Query\Builder;

use Magento\Framework\Search\Request\QueryInterface;

class BoolExpression extends AbstractBuilder
{
    const QUERY_CONDITION_MUST   = 'must';
    const QUERY_CONDITION_NOT    = 'must_not';
    const QUERY_CONDITION_SHOULD = 'should';

    public function buildQuery(QueryInterface $query)
    {
        $searchQuery = [];
        $clauses = [self::QUERY_CONDITION_MUST, self::QUERY_CONDITION_NOT, self::QUERY_CONDITION_SHOULD];

        foreach ($clauses as $clause) {
            $queries = array_map([$this->builder, 'buildQuery'], $this->getQueryClause($query, $clause));
            $searchQuery[$clause] = array_filter($queries);
        }

        return ['bool' => $searchQuery];
    }

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