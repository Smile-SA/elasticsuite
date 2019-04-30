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

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Build Elasticsearch queries from search request QueryInterface queries.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Builder implements BuilderInterface
{
    /**
     * @var BuilderInterface[]
     */
    private $builders;

    /**
     * Constructor.
     *
     * @param BuilderInterface[] $builders Builders implementations.
     */
    public function __construct(array $builders = [])
    {
        $this->builders = $builders;
    }

    /**
     * Build the ES query from a Query
     *
     * @param QueryInterface $query Query to be built.
     *
     * @return array
     */
    public function buildQuery(QueryInterface $query)
    {
        $searchQuery = false;
        $builder     = $this->getBuilder($query);

        if ($builder !== null) {
            $searchQuery = $builder->buildQuery($query);
        }

        return $searchQuery;
    }

    /**
     * Retrieve the builder used to build a query.
     *
     * @param QueryInterface $query Query to be built.
     *
     * @return BuilderInterface
     */
    private function getBuilder($query)
    {
        $queryType = $query->getType();

        if (!isset($this->builders[$queryType])) {
            throw new \InvalidArgumentException("Unknow query builder for {$queryType}.");
        }

        return $this->builders[$queryType];
    }
}
