<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Query\Builder\Span;

use Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Query\Builder\AbstractComplexQueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface;

/**
 * Override of AbstractComplexQueryBuilder for providing span query classes.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
abstract class AbstractComplexSpanQueryBuilder extends AbstractComplexQueryBuilder
{
    /**
     * Mock a sub query.
     *
     * @param string $queryName Query name.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getSubQueryMock($queryName)
    {
        $mock = $this->getMockBuilder(SpanQueryInterface::class)->getMock();
        $mock->method('getName')->will($this->returnValue($queryName));

        return $mock;
    }
}
