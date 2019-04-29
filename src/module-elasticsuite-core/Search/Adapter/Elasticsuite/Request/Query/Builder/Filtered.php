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
 * @copyright 2019 Smile
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
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Filtered extends AbstractComplexBuilder implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryInterface $query)
    {
        if ($query->getType() !== QueryInterface::TYPE_FILTER) {
            throw new \InvalidArgumentException("Query builder : invalid query type {$query->getType()}");
        }

        $searchQuery = [];

        if ($query->getFilter()) {
            $searchQuery['filter'] = $this->parentBuilder->buildQuery($query->getFilter());
        }

        if ($query->getQuery()) {
            $searchQuery['must'] = $this->parentBuilder->buildQuery($query->getQuery());
        }

        if ($query->getName()) {
            $searchQuery['_name'] = $query->getName();
        }

        $queryType = isset($searchQuery['must']) ? 'bool' : 'constant_score';

        if ($queryType === 'constant_score' && !isset($searchQuery['filter'])) {
            $searchQuery['filter'] = ['match_all' => new \stdClass()];
        }

        $searchQuery['boost'] = $query->getBoost();

        return [$queryType => $searchQuery];
    }
}
