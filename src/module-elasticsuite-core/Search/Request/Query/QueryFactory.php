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
namespace Smile\ElasticsuiteCore\Search\Request\Query;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Factory for search request queries.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class QueryFactory
{
    /**
     * @var array
     */
    private $factories;

    /**
     * Constructor.
     *
     * @param array $factories Query factories by type.
     */
    public function __construct($factories = [])
    {
        $this->factories = $factories;
    }

    /**
     * Create a query from it's type and params.
     *
     * @param string $queryType   Query type (must be a valid query type defined into the factories array).
     * @param array  $queryParams Query constructor params.
     *
     * @return QueryInterface
     */
    public function create($queryType, $queryParams)
    {
        if (!isset($this->factories[$queryType])) {
            throw new \LogicException("No factory found for query of type {$queryType}");
        }

        return $this->factories[$queryType]->create($queryParams);
    }
}
