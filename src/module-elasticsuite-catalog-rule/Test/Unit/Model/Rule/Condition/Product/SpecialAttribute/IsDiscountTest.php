<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Test\Unit\Model\Rule\Condition\Product\SpecialAttribute;

use Magento\Config\Model\Config\Source\Yesno;
use Magento\Customer\Model\Session;
use PHPUnit\Framework\MockObject\MockObject;
use Smile\ElasticsuiteCatalogRule\Api\Rule\QueryContextResolverInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute\IsDiscount;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryContext\CustomerGroupProvider;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Boolean;
use Smile\ElasticsuiteCore\Search\Request\Query\Nested;
use Smile\ElasticsuiteCore\Search\Request\Query\Term;

/**
 * Is Discount special attribute rule unit test.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 */
class IsDiscountTest extends AbstractSpecialAttribute
{
    /**
     * Test default methods returns.
     *
     * @return void
     */
    public function testDefaultReturns()
    {
        $booleanSource = new Yesno();
        $isDiscount = new IsDiscount(
            $booleanSource,
            $this->getCustomerSessionMock(),
            $this->getQueryFactory(),
            $this->getQueryContextResolverMock()
        );

        $this->assertEquals('price.is_discount', $isDiscount->getAttributeCode());
        $this->assertEquals(' ', $isDiscount->getOperatorName());
        $this->assertEquals('select', $isDiscount->getInputType());
        $this->assertEquals('hidden', $isDiscount->getValueElementType());
        $this->assertEquals(' ', $isDiscount->getValueName('randomValue'));
        $this->assertEquals(true, $isDiscount->getValue('randomValue'));
        $this->assertEquals($booleanSource->toOptionArray(), $isDiscount->getValueOptions());
        $this->assertEquals(__('Only discounted products'), $isDiscount->getLabel());
    }

    /**
     * Test search query building.
     *
     * @return void
     */
    public function testGetSearchQuery()
    {
        $booleanSource = new Yesno();
        $isDiscount = new IsDiscount(
            $booleanSource,
            $this->getCustomerSessionMock(),
            $this->getQueryFactory(),
            $this->getQueryContextResolverMock()
        );

        $searchQuery = $isDiscount->getSearchQuery($this->getProductConditionMock());
        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Nested::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_NESTED, $searchQuery->getType());

        /** @var Nested $searchQuery */
        $this->assertEquals('price', $searchQuery->getPath());
        $this->assertInstanceOf(Boolean::class, $searchQuery->getQuery());
        $booleanQuery = $searchQuery->getQuery();

        /** @var Boolean $booleanQuery */
        $this->assertEmpty($booleanQuery->getShould());
        $this->assertEmpty($booleanQuery->getMustNot());
        $this->assertCount(2, $booleanQuery->getMust());
        $mustClauses = $booleanQuery->getMust();

        $this->assertInstanceOf(Term::class, $mustClauses[0]);
        $clause = $mustClauses[0];
        /** @var Term $clause */
        $this->assertEquals('price.customer_group_id', $clause->getField());
        $this->assertEquals(0, $clause->getValue());

        $this->assertInstanceOf(Term::class, $mustClauses[1]);
        $clause = $mustClauses[1];
        /** @var Term $clause */
        $this->assertEquals('price.is_discount', $clause->getField());
        $this->assertEquals(true, $clause->getValue());
    }

    /**
     * Test that the query is built for the customer group given by the query context resolver, the value used to vary
     * the cache keys of the queries embedding it, rather than for the customer group of the session.
     *
     * @return void
     */
    public function testGetSearchQueryCustomerGroupFromQueryContext()
    {
        $isDiscount = new IsDiscount(
            new Yesno(),
            $this->getCustomerSessionMock(0),
            $this->getQueryFactory(),
            $this->getQueryContextResolverMock(3)
        );

        $searchQuery = $isDiscount->getSearchQuery($this->getProductConditionMock());

        /** @var Nested $searchQuery */
        $clause = $searchQuery->getQuery()->getMust()[0];
        /** @var Term $clause */
        $this->assertEquals('price.customer_group_id', $clause->getField());
        $this->assertSame(3, $clause->getValue());
        $this->assertSame([CustomerGroupProvider::CODE], $isDiscount->getContextDependencies());
    }

    /**
     * Get Product Condition mock object.
     *
     * @param int $customerGroupId Customer group id.
     *
     * @return Session|MockObject
     */
    private function getCustomerSessionMock($customerGroupId = 0)
    {
        $customerSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $customerSession->method('getCustomerGroupId')->willReturn($customerGroupId);

        return $customerSession;
    }

    /**
     * Get query context resolver mock object.
     *
     * @param int $customerGroupId Customer group id.
     *
     * @return QueryContextResolverInterface|MockObject
     */
    private function getQueryContextResolverMock($customerGroupId = 0)
    {
        $queryContextResolver = $this->getMockBuilder(QueryContextResolverInterface::class)->getMock();
        $queryContextResolver->method('getValues')
            ->with([CustomerGroupProvider::CODE])
            ->willReturn([CustomerGroupProvider::CODE => (string) $customerGroupId]);

        return $queryContextResolver;
    }
}
