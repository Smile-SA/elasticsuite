<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Test\Unit\Model\Rule;

use Magento\Customer\Model\Session;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogRule\Api\Rule\QueryContext\ProviderInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute\IsDiscount;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryContextResolver;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryContext\CustomerGroupProvider;

/**
 * Unit test for the values of the contexts rule search queries can depend on.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class QueryContextResolverTest extends TestCase
{
    /**
     * Values are normalized as strings usable in a cache key.
     *
     * @return void
     */
    public function testValueNormalization(): void
    {
        $longValue    = str_repeat('a', 65);
        $queryContextResolver = new QueryContextResolver(
            [
                'int'    => $this->getProvider(3),
                'bool'   => $this->getProvider(true),
                'array'  => $this->getProvider([12, '3', 7, 3]),
                'long'   => $this->getProvider($longValue),
                'string' => $this->getProvider('drive'),
            ]
        );

        $this->assertSame(
            [
                'array'  => '3,7,12',
                'bool'   => '1',
                'int'    => '3',
                'long'   => '#' . substr(sha1($longValue), 0, 12),
                'string' => 'drive',
            ],
            $queryContextResolver->getValues(['array', 'bool', 'int', 'long', 'string'])
        );
        $this->assertSame([], $queryContextResolver->getValues([]));
    }

    /**
     * A context without provider is a configuration error.
     *
     * @return void
     */
    public function testUnknownContext(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('"retailer"');

        (new QueryContextResolver(['customer_group' => $this->getProvider(0)]))->getValues(['retailer']);
    }

    /**
     * The customer group context, which "is discount" conditions depend on.
     *
     * @return void
     */
    public function testCustomerGroup(): void
    {
        $customerSession = $this->createMock(Session::class);
        $customerSession->method('getCustomerGroupId')->willReturn('2');
        $queryContextResolver = new QueryContextResolver(
            [CustomerGroupProvider::CODE => new CustomerGroupProvider($customerSession)]
        );

        $this->assertSame(['customer_group' => '2'], $queryContextResolver->getValues(['customer_group']));

        $isDiscount = $this->getMockBuilder(IsDiscount::class)->disableOriginalConstructor()
            ->onlyMethods(['getSearchQuery'])->getMock();
        $this->assertSame([CustomerGroupProvider::CODE], $isDiscount->getContextDependencies());
    }

    /**
     * Context value provider returning the given value.
     *
     * @param mixed $value Context value.
     *
     * @return ProviderInterface
     */
    private function getProvider($value): ProviderInterface
    {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('getValue')->willReturn($value);

        return $provider;
    }
}
