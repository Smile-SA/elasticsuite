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

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\BuilderInterface;

/**
 * Build an ES prefix query.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Prefix implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryInterface $query)
    {
        if ($query->getType() !== QueryInterface::TYPE_PREFIX) {
            throw new \InvalidArgumentException("Query builder : invalid query type {$query->getType()}");
        }

        $searchQueryParams = [
            'value' => $query->getValue(),
            'boost' => $query->getBoost(),
        ];

        $searchQuery = ['prefix' => [$query->getField() => $searchQueryParams]];

        if ($query->getName()) {
            $searchQuery['prefix']['_name'] = $query->getName();
        }

        return $searchQuery;
    }
}
