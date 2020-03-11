<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\BuilderInterface;

/**
 * Build an ES match_phrase_prefix query.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class MatchPhrasePrefix implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryInterface $query)
    {
        if ($query->getType() !== QueryInterface::TYPE_MATCHPHRASEPREFIX) {
            throw new \InvalidArgumentException("Query builder : invalid query type {$query->getType()}");
        }

        $searchQueryParams = [
            'query'          => $query->getQueryText(),
            'boost'          => $query->getBoost(),
            'max_expansions' => $query->getMaxExpansions(),
        ];

        $searchQuery = ['match_phrase_prefix' => [$query->getField() => $searchQueryParams]];

        if ($query->getName()) {
            $searchQuery['match_phrase_prefix']['_name'] = $query->getName();
        }

        return $searchQuery;
    }
}
