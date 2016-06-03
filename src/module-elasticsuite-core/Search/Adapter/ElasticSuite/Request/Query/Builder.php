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

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Build ElasticSearch queries from search request QueryInterface queries.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Builder implements BuilderInterface
{
    /**
     * @var array
     */
    private $queryBuilderClasses = [
        QueryInterface::TYPE_BOOL       => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder\Boolean',
        QueryInterface::TYPE_FILTER     => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder\Filtered',
        QueryInterface::TYPE_NOT        => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder\Not',
        QueryInterface::TYPE_NESTED     => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder\Nested',
        QueryInterface::TYPE_TERM       => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder\Term',
        QueryInterface::TYPE_TERMS      => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder\Terms',
        QueryInterface::TYPE_RANGE      => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder\Range',
        QueryInterface::TYPE_MATCH      => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder\Match',
        QueryInterface::TYPE_COMMON     => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder\Common',
        QueryInterface::TYPE_MULTIMATCH => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder\MultiMatch',
    ];

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager Object Manager.
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
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
        $builder = $this->getBuilder($query);

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
     * @return BuilderInterface|null
     */
    private function getBuilder($query)
    {
        $builder = null;
        $queryType = $query->getType();

        if (isset($this->queryBuilderClasses[$queryType])) {
            $builderClass = $this->queryBuilderClasses[$queryType];
            $builder = $this->objectManager->get($builderClass, ['builder' => $this]);
        }

        return $builder;
    }
}
