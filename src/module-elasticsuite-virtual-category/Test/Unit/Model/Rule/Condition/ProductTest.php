<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Model\Rule\Condition;

use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteVirtualCategory\Helper\Config as VirtualCategoryConfig;
use Smile\ElasticsuiteVirtualCategory\Model\Rule;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product;

/**
 * Unit test for the category conditions of {@see Product}: categories being built are left out, except the
 * expansion boundaries.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class ProductTest extends TestCase
{
    /**
     * Test the categories whose query is used by a category condition.
     *
     * @dataProvider categoryConditionDataProvider
     *
     * @param string $operator           Condition operator.
     * @param string $value              Condition value.
     * @param int[]  $excludedCategories Categories being built.
     * @param int[]  $boundaryIds        Expansion boundaries.
     * @param int[]  $expectedIds        Categories expected to be queried.
     *
     * @return void
     */
    public function testCategoryCondition(
        string $operator,
        string $value,
        array $excludedCategories,
        array $boundaryIds,
        array $expectedIds
    ): void {
        $queriedIds = [];
        $rule = $this->createMock(Rule::class);
        $rule->method('getCategorySearchQuery')->willReturnCallback(
            function ($categoryId, $excluded) use (&$queriedIds, $excludedCategories) {
                $this->assertSame($excludedCategories, $excluded);
                $queriedIds[] = (int) $categoryId;

                return $this->createMock(QueryInterface::class);
            }
        );

        $config = $this->createMock(VirtualCategoryConfig::class);
        $config->method('getVirtualRulesExpansionBoundaries')->willReturn($boundaryIds);

        $queryFactory = $this->createMock(QueryFactory::class);
        $queryFactory->method('create')->willReturn($this->createMock(QueryInterface::class));

        $condition = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->getMock();
        $condition->method('getValue')->willReturn($value);
        $condition->setData('operator', $operator);
        $condition->setData('rule', $rule);
        $this->setPrivateProperty($condition, 'queryFactory', $queryFactory);
        $this->setPrivateProperty($condition, 'virtualCategoryConfig', $config);

        $method = new \ReflectionMethod(Product::class, 'getCategorySearchQuery');
        $method->setAccessible(true);
        $this->assertNotNull($method->invoke($condition, $excludedCategories));
        $this->assertSame($expectedIds, $queriedIds);
    }

    /**
     * Data provider for {@see testCategoryCondition()}.
     *
     * @return array
     */
    public static function categoryConditionDataProvider(): array
    {
        return [
            'nothing being built'          => ['()', '10,20', [], [10], [10, 20]],
            'category being built left out' => ['()', '10,20', [20], [], [10]],
            'boundary being built kept'     => ['()', '10,20', [10, 20], [10], [10]],
            'negation keeps everything'     => ['!()', '10,20', [10, 20], [], [10, 20]],
        ];
    }

    /**
     * Set a private property of the tested class.
     *
     * @param object $object   Object.
     * @param string $property Property name.
     * @param mixed  $value    Value.
     *
     * @return void
     */
    private function setPrivateProperty($object, string $property, $value): void
    {
        $reflection = new \ReflectionProperty(Product::class, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
