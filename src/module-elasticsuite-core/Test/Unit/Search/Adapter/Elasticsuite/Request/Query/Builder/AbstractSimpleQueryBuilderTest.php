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
 * Common method used to test query builders.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class AbstractSimpleQueryBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test using the query builder with an invalid query type throws an exception.
     *
     * @return void
     */
    public function testInvalidQuery()
    {
        $this->expectExceptionMessage("Query builder : invalid query type invalid_type");
        $this->expectException(\InvalidArgumentException::class);
        $builder = $this->getQueryBuilder();

        $query = $this->getMockBuilder(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::class)->getMock();
        $query->method('getType')->will($this->returnValue('invalid_type'));

        $builder->buildQuery($query);
    }

    /**
     * Currently tested builder.
     *
     * @return \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\BuilderInterface
     */
    abstract protected function getQueryBuilder();
}
