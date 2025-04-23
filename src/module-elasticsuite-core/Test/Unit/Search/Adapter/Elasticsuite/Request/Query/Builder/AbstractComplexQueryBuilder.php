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
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Query\Builder;

/**
 * Common methods used to test query composed builders (bool, not, ...).
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
abstract class AbstractComplexQueryBuilder extends AbstractSimpleQueryBuilder
{
    /**
     * Return a mocked parent query builder used to build subqueries.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getParentQueryBuilder()
    {
        $mock = $this->getMockBuilder(\Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $buildQueryCallback = function (\Smile\ElasticsuiteCore\Search\Request\QueryInterface $query) {
            return $query->getName();
        };

        $mock->method('buildQuery')->will($this->returnCallback($buildQueryCallback));

        return $mock;
    }

    /**
     * Mock a sub query.
     *
     * @param string $queryName Query name.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getSubQueryMock($queryName)
    {
        $mock = $this->getMockBuilder(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::class)->getMock();
        $mock->method('getName')->will($this->returnValue($queryName));

        return $mock;
    }
}
