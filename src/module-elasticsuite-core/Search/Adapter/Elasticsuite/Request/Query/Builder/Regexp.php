<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\BuilderInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Build an ES regexp query.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class Regexp implements BuilderInterface
{
    /**
     * @var string
     */
    const DEFAULT_FLAGS = 'NONE';

    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryInterface $query)
    {
        if ($query->getType() !== QueryInterface::TYPE_REGEXP) {
            throw new \InvalidArgumentException("Query builder : invalid query type {$query->getType()}");
        }

        $searchQueryParams = [
            'value' => $query->getValue(),
            'boost' => $query->getBoost(),
            'flags' => self::DEFAULT_FLAGS,
        ];

        $searchQuery = ['regexp' => [$query->getField() => $searchQueryParams]];

        if ($query->getName()) {
            $searchQuery['regexp']['_name'] = $query->getName();
        }

        return $searchQuery;
    }
}
