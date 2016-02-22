<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Query;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;
use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Query\Builder\AbstractBuilder;

class Builder
{
    private $queryBuilderClasses = [
        QueryInterface::TYPE_BOOL   => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Query\Builder\BoolExpression',
        QueryInterface::TYPE_FILTER => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Query\Builder\Filtered',
        QueryInterface::TYPE_NESTED => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Query\Builder\Nested',
        QueryInterface::TYPE_TERMS  => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Query\Builder\Terms',
        QueryInterface::TYPE_RANGE  => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Query\Builder\Range',
        QueryInterface::TYPE_MATCH  => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Query\Builder\Match',
        //QueryInterface::TYPE_FILTER => 'Smile\ElasticSuiteCore\Search\Request\Query\Filtered',
        //QueryInterface::TYPE_TERM   => 'Smile\ElasticSuiteCore\Search\Request\Query\Term',
        //QueryInterface::TYPE_RANGE  => 'Smile\ElasticSuiteCore\Search\Request\Query\Range',
    ];

    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @return array
     */
    public function buildQuery($query)
    {
        $searchQuery = false;
        $builder = $this->getBuilder($query);
        if ($builder) {
            $searchQuery = $builder->buildQuery($query);
        }

        return $searchQuery;
    }

    /**
     *
     * @param AbstractBuilder $query
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