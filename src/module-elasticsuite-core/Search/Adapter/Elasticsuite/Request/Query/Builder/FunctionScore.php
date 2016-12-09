<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\BuilderInterface;

/**
 * Build an ES filtered query.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class FunctionScore extends AbstractComplexBuilder implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryInterface $query)
    {
        $searchQueryParams = [
            'boost'      => $query->getBoost(),
            'score_mode' => $query->getScoreMode(),
            'boost_mode' => $query->getBoostMode(),
            'functions'  => $query->getFunctions(),
        ];

        if ($query->getQuery()) {
            $searchQueryParams['query'] = $this->parentBuilder->buildQuery($query->getQuery());
        }

        foreach ($searchQueryParams['functions'] as &$function) {
            if (isset($function['filter'])) {
                $function['filter'] = $this->parentBuilder->buildQuery($function['filter']);
            }
        }

        return ['function_score' => $searchQueryParams];
    }
}
