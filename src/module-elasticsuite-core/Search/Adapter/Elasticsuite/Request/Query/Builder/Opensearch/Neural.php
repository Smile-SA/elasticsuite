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
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Opensearch;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\BuilderInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Vector\Opensearch\Neural as NeuralQuery;

/**
 * neural query implementation, for Opensearch.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Neural implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryInterface $query): array
    {
        if (NeuralQuery::TYPE_NEURAL !== $query->getType()) {
            throw new \InvalidArgumentException("Query builder : invalid query type {$query->getType()}");
        }

        /** @var NeuralQuery $query */
        $queryParams = [
            'query_text' => $query->getQueryText(),
            'k'          => $query->getKValue(),
            'boost'      => $query->getBoost(),
        ];

        if ($query->getModelId()) {
            $queryParams['model_id'] = $query->getModelId();
        }

        if ($query->getName()) {
            $queryParams['_name'] = $query->getName();
        }

        return ['neural' => [$query->getField() => $queryParams]];
    }
}
