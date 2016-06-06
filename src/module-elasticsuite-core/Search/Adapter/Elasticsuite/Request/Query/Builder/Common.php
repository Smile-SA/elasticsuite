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
 * Build an ES common query.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Common implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryInterface $query)
    {
        $searchQueryParams = [
            'query'                => $query->getQueryText(),
            'minimum_should_match' => $query->getMinimumShouldMatch(),
            'cutoff_frequency'     => $query->getCutoffFrequency(),
            'boost'                => $query->getBoost(),
        ];

        return ['common' => [$query->getField() => $searchQueryParams]];
    }
}
