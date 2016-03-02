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
namespace Smile\ElasticSuiteCore\Search\Request\Query;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Factory for search request queries.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class QueryFactory
{
    /**
     * @var array
     */
    private $factories = [
        QueryInterface::TYPE_BOOL       => 'Smile\ElasticSuiteCore\Search\Request\Query\BoolFactory',
        QueryInterface::TYPE_FILTER     => 'Smile\ElasticSuiteCore\Search\Request\Query\FilteredFactory',
        QueryInterface::TYPE_NESTED     => 'Smile\ElasticSuiteCore\Search\Request\Query\NestedFactory',
        QueryInterface::TYPE_TERM       => 'Smile\ElasticSuiteCore\Search\Request\Query\TermFactory',
        QueryInterface::TYPE_TERMS      => 'Smile\ElasticSuiteCore\Search\Request\Query\TermsFactory',
        QueryInterface::TYPE_RANGE      => 'Smile\ElasticSuiteCore\Search\Request\Query\RangeFactory',
        QueryInterface::TYPE_MATCH      => 'Smile\ElasticSuiteCore\Search\Request\Query\MatchFactory',
        QueryInterface::TYPE_COMMON     => 'Smile\ElasticSuiteCore\Search\Request\Query\CommonFactory',
        QueryInterface::TYPE_MULTIMATCH => 'Smile\ElasticSuiteCore\Search\Request\Query\MultiMatchFactory',
    ];

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager Object manager.
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
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

        $factory = $this->objectManager->get($this->factories[$queryType]);

        return $factory->create($queryParams);
    }
}
