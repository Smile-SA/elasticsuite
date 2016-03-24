<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;
use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\BuilderInterface;

/**
 * Build an ES multi match match query.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class MultiMatch implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryInterface $query)
    {
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
        ];

        if ($query->getMatchType()) {
            $searchQueryParams['type'] = $query->getMatchType();
        }

        if ($query->getCutoffFrequency()) {
            $searchQueryParams['cutoff_frequency'] = $query->getCutoffFrequency();
        }

        if ($query->getFuzzinessConfiguration()) {
            $searchQueryParams['fuzziness'] = $query->getFuzzinessConfiguration()->getValue();
            $searchQueryParams['prefix_length'] = $query->getFuzzinessConfiguration()->getPrefixLength();
            $searchQueryParams['max_expansions'] = $query->getFuzzinessConfiguration()->getMaxExpansion();
        }

        return ['multi_match' => $searchQueryParams];
    }
}
