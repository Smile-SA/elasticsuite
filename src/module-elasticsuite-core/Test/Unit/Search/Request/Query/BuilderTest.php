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

use Smile\ElasticsuiteCore\Api\Search\ContextInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Builder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;

/**
 * Search request query builder test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class BuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test creating a query from a fulltext search and filters.
     *
     * @return void
     */
    public function testCreateQuery()
    {
        $builder = new Builder(
            $this->getQueryFactory(),
            $this->getFulltextQueryBuilder(),
            $this->getFilterQueryBuilder(),
            $this->getSearchContext()
        );

        $query = $builder->createQuery(
            $this->getContainerConfiguration(),
            'test',
            ['filter'],
            SpellcheckerInterface::SPELLING_TYPE_EXACT
        );

        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertEquals(QueryInterface::TYPE_FILTER, $query->getType());

        $this->assertInstanceOf(QueryInterface::class, $query->getQuery());
        $this->assertEquals('fulltextQuery', $query->getQuery()->getType());

        $this->assertInstanceOf(QueryInterface::class, $query->getFilter());
        $this->assertEquals('filterQuery', $query->getFilter()->getType());
    }

    /**
     * Mocks the search context.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getSearchContext()
    {
        return $this->getMockBuilder(ContextInterface::class)->getMock();
    }

    /**
     * Mocks the container configration.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getContainerConfiguration()
    {
        $containerConfiguration = $this->getMockBuilder(ContainerConfigurationInterface::class)->getMock();

        $containerConfiguration->method('getFilters')->will($this->returnValue([]));

        return $containerConfiguration;
    }

    /**
     * Mocks the query factory used by the tested builder.
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getQueryFactory()
    {
        $queryFactory = $this->getMockBuilder(QueryFactory::class)->getMock();

        $createQueryCallback = function ($type, $params) {
            $queryClass = new \ReflectionClass('\Smile\ElasticsuiteCore\Search\Request\Query\Filtered');

            return $queryClass->newInstanceArgs($params);
        };

        $queryFactory->method('create')->will($this->returnCallback($createQueryCallback));

        return $queryFactory;
    }

    /**
     * Mocks the fulltext query builder.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getFulltextQueryBuilder()
    {
        return $this->getQueryBuilder(\Smile\ElasticsuiteCore\Search\Request\Query\Fulltext\QueryBuilder::class, 'fulltextQuery');
    }

    /**
     * Mocks the filters query builder.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getFilterQueryBuilder()
    {
        return $this->getQueryBuilder(\Smile\ElasticsuiteCore\Search\Request\Query\Filter\QueryBuilder::class, 'filterQuery');
    }

    /**
     * Mock a query builder that creates query with the indicated type.
     *
     * @param string $class Mocked class name.
     * @param string $name  Mock returned query type.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getQueryBuilder($class, $name)
    {
        $query = $this->getMockBuilder(QueryInterface::class)->getMock();
        $query->method('getType')->will($this->returnValue($name));

        $queryBuilder = $this->getMockBuilder($class)->disableOriginalConstructor()->getMock();
        $queryBuilder->method('create')->will($this->returnValue($query));

        return $queryBuilder;
    }
}
