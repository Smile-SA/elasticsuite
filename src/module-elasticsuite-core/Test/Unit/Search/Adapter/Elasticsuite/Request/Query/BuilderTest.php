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
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Query;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\BuilderInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Search adapter query builder test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class BuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test building a valid query.
     *
     * @return void
     */
    public function testBuildValidQuery()
    {
        $query = $this->getMockBuilder(QueryInterface::class)->getMock();
        $query->method('getType')->will($this->returnValue('queryType'));

        $this->assertEquals(['type' => 'queryType'], $this->getQueryBuilder()->buildQuery($query));
    }

    /**
     * Test the query builder throws an exception when using an invalid query type.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unknow query builder for invalidQueryType.
     *
     * @return void
     */
    public function testBuildInvalidQuery()
    {
        $query = $this->getMockBuilder(QueryInterface::class)->getMock();
        $query->method('getType')->will($this->returnValue('invalidQueryType'));

        $this->getQueryBuilder()->buildQuery($query);
    }

    /**
     * Mock a query builder.
     *
     * @return \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder
     */
    private function getQueryBuilder()
    {
        $queryBuilderMock = $this->getMockBuilder(BuilderInterface::class)->getMock();

        $buildQueryCallback = function (QueryInterface $query) {
            return ['type' => $query->getType()];
        };

        $queryBuilderMock->method('buildQuery')->will($this->returnCallback($buildQueryCallback));

        return new QueryBuilder(['queryType' => $queryBuilderMock]);
    }
}
