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
 * Build an ES nested query.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Nested extends AbstractComplexBuilder implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryInterface $query)
    {
        if ($query->getType() !== QueryInterface::TYPE_NESTED) {
            throw new \InvalidArgumentException("Query builder : invalid query type {$query->getType()}");
        }

        $queryParams = [
            'path'       => $query->getPath(),
            'score_mode' => $query->getScoreMode(),
            'query'      => $this->parentBuilder->buildQuery($query->getQuery()),
            'boost'      => $query->getBoost(),
        ];

        if ($query->getName()) {
            $queryParams['_name'] = $query->getName();
        }

        return ['nested' => $queryParams];
    }
}
