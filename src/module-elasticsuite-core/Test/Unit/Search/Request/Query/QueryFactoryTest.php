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
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Request\Query;

use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Search request query builder test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class QueryFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test the query creation using the factory.
     *
     * @return void
     */
    public function testQueryCreate()
    {
        $query = $this->getQueryFactory()->create('queryType', []);
        $this->assertInstanceOf(QueryInterface::class, $query);
    }

    /**
     * Test submitting an invalid query type throws an exception.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage No factory found for query of type invalidQueryType
     *
     * @return void
     */
    public function testInvalidQueryCreate()
    {
        $this->getQueryFactory()->create('invalidQueryType', []);
    }

    /**
     * Prepared a mocked query factory.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private function getQueryFactory()
    {
        $queryMock = $this->getMockBuilder(QueryInterface::class)->getMock();

        $factoryName      = sprintf("%s%s", QueryInterface::class, 'Factory');
        $queryFactoryMock = $this->getMockBuilder($factoryName)
            ->setMethods(['create'])
            ->getMock();

        $queryFactoryMock->method('create')
            ->will($this->returnValue($queryMock));

        $factories = ['queryType' => $queryFactoryMock];

        return new QueryFactory($factories);
    }
}
