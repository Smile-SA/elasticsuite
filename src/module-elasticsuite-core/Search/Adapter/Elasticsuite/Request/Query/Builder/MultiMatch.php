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

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\BuilderInterface;

/**
 * Build an ES multi match match query.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class MultiMatch implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryInterface $query)
    {
        if ($query->getType() !== QueryInterface::TYPE_MULTIMATCH) {
            throw new \InvalidArgumentException("Query builder : invalid query type {$query->getType()}");
        }

        $fields = [];

        foreach ($query->getFields() as $field => $weight) {
            $fields[] = sprintf("%s^%s", $field, $weight);
        }

        $searchQueryParams = [
            'query'                => $query->getQueryText(),
            'fields'               => $fields,
            'minimum_should_match' => $query->getMinimumShouldMatch(),
            'tie_breaker'          => $query->getTieBreaker(),
            'boost'                => $query->getBoost(),
            'type'                 => $query->getMatchType(),
        ];

        // The cutoff_frequency is deprecated with ES >= 8.0.
        if ($query->getCutoffFrequency()) {
            $searchQueryParams['cutoff_frequency'] = $query->getCutoffFrequency();
        }

        if ($query->getFuzzinessConfiguration()) {
            $searchQueryParams['fuzziness'] = $query->getFuzzinessConfiguration()->getValue();
            $searchQueryParams['prefix_length'] = $query->getFuzzinessConfiguration()->getPrefixLength();
            $searchQueryParams['max_expansions'] = $query->getFuzzinessConfiguration()->getMaxExpansion();
            $searchQueryParams['minimum_should_match'] = $query->getFuzzinessConfiguration()->getMinimumShouldMatch();
        }

        if ($query->getName()) {
            $searchQueryParams['_name'] = $query->getName();
        }

        return ['multi_match' => $searchQueryParams];
    }
}
