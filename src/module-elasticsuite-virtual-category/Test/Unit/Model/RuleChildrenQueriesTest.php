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

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\ProductFactory as ProductConditionFactory;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteVirtualCategory\Helper\Config;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory;
use Smile\ElasticsuiteVirtualCategory\Model\Rule;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product as ProductCondition;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\DependencyCollector;

/**
 * Unit test for the children queries of a virtual category: a virtual descendant is not expanded again when the
 * query of one of its virtual ancestors, which expands its own descendants, is already part of the query.
 *
 * The tested virtual category 42 (path 1/2/42) has the following descendants, sorted by level as returned by
 * the children collection:
 * - 100 (virtual), 400 (virtual), 500 (standard)
 * -   - 200 (virtual, child of 100), 300 (standard, child of 100)
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class RuleChildrenQueriesTest extends TestCase
{
    /**
     * @var int[]
     */
    private $builtIds = [];

    /**
     * @var int[]
     */
    private $standardIds = [];

    /**
     * @var QueryInterface[]
     */
    private $should = [];

    /**
     * @var Collection|MockObject
     */
    private $collection;

    /**
     * Descendants of a virtual category already expanding them are left out.
     *
     * @return void
     */
    public function testVirtualDescendantsAreNotExpandedTwice(): void
    {
        $this->addChildrenQueries($this->getRule());

        $this->assertSame([100, 400], $this->builtIds);
        $this->assertSame([500], $this->standardIds);
        // Category own query, 100, 400 and the standard children.
        $this->assertCount(4, $this->should);
    }

    /**
     * An expansion boundary does not expand its descendants: they are handled by the category query itself.
     *
     * @return void
     */
    public function testDescendantsOfABoundaryAreExpanded(): void
    {
        $this->addChildrenQueries($this->getRule([100]));

        $this->assertSame([100, 400, 200], $this->builtIds);
        $this->assertSame([500, 300], $this->standardIds);
    }

    /**
     * Descendants of a virtual category without query (eg. disabled without forcing zero results) are handled
     * by the category query itself.
     *
     * @return void
     */
    public function testDescendantsOfAVirtualCategoryWithoutQueryAreExpanded(): void
    {
        $this->addChildrenQueries($this->getRule([], [100]));

        $this->assertSame([100, 400, 200], $this->builtIds);
        $this->assertSame([500, 300], $this->standardIds);
    }

    /**
     * Descendants of a virtual category left out because being built are handled by the category query itself.
     *
     * @return void
     */
    public function testDescendantsOfALeftOutVirtualCategoryAreExpanded(): void
    {
        $this->addChildrenQueries($this->getRule(), [100]);

        $this->assertSame([400, 200], $this->builtIds);
        $this->assertSame([500, 300], $this->standardIds);
    }

    /**
     * Descendants are sorted by level, so that a virtual category is handled before its own descendants.
     *
     * @return void
     */
    public function testChildrenAreSortedByLevel(): void
    {
        $rule = $this->getRule();
        $this->collection->expects($this->once())->method('setOrder')->with('level', 'ASC')->willReturnSelf();

        $this->addChildrenQueries($rule);
    }

    /**
     * Build the children queries of the virtual category 42.
     *
     * @param Rule  $rule               Rule.
     * @param int[] $excludedCategories Categories being built.
     *
     * @return void
     */
    private function addChildrenQueries(Rule $rule, array $excludedCategories = []): void
    {
        $category = $this->getCategory(42, '1/2/42', true);
        $method   = new \ReflectionMethod(Rule::class, 'addChildrenQueries');
        $method->setAccessible(true);
        $method->invoke($rule, $this->createMock(QueryInterface::class), $category, $excludedCategories);
    }

    /**
     * Rule whose dependencies are mocked, and whose (recursive) category queries are stubbed.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param int[] $boundaries   Expansion boundaries.
     * @param int[] $withoutQuery Virtual categories without query.
     *
     * @return Rule
     */
    private function getRule(array $boundaries = [], array $withoutQuery = []): Rule
    {
        $config = $this->createMock(Config::class);
        $config->method('getVirtualRulesExpansionBoundaries')->willReturn($boundaries);

        $children = [
            $this->getCategory(100, '1/2/42/100', true),
            $this->getCategory(400, '1/2/42/400', true),
            $this->getCategory(500, '1/2/42/500', false),
            $this->getCategory(200, '1/2/42/100/200', true),
            $this->getCategory(300, '1/2/42/100/300', false),
        ];
        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'setStoreId', 'addIsActiveFilter', 'addPathFilter', 'setOrder', 'addFieldToFilter',
                    'addAttributeToSelect', 'getSize', 'getIterator',
                ]
            )
            ->getMock();
        foreach (['setStoreId', 'addIsActiveFilter', 'addPathFilter', 'addFieldToFilter', 'addAttributeToSelect'] as $m) {
            $this->collection->method($m)->willReturnSelf();
        }
        $this->collection->method('getSize')->willReturn(count($children));
        $this->collection->method('getIterator')->willReturn(new \ArrayIterator($children));
        $collectionFactory = $this->createMock(CollectionFactory::class);
        $collectionFactory->method('create')->willReturn($this->collection);

        $productConditionFactory = $this->createMock(ProductConditionFactory::class);
        $productConditionFactory->method('create')->willReturnCallback(
            function (array $params) {
                $this->standardIds = $params['data']['value'];

                return $this->createMock(ProductCondition::class);
            }
        );
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getSearchQuery')->willReturn($this->createMock(QueryInterface::class));

        $queryFactory = $this->createMock(QueryFactory::class);
        $queryFactory->method('create')->willReturnCallback(
            function (string $type, array $params) {
                $this->should = $params['should'];

                return $this->createMock(QueryInterface::class);
            }
        );

        /** @var Rule|MockObject $rule */
        $rule = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCategorySearchQuery'])
            ->getMock();
        $rule->method('getCategorySearchQuery')->willReturnCallback(
            function ($category) use ($withoutQuery) {
                $this->builtIds[] = (int) $category->getId();
                if (in_array((int) $category->getId(), $withoutQuery, true)) {
                    return null;
                }

                return $this->createMock(QueryInterface::class);
            }
        );
        $rule->setData('store_id', 1);
        $properties = [
            'config'                    => $config,
            'categoryCollectionFactory' => $collectionFactory,
            'productConditionsFactory'  => $productConditionFactory,
            'queryBuilder'              => $queryBuilder,
            'queryFactory'              => $queryFactory,
            'dependencyCollector'       => $this->createMock(DependencyCollector::class),
        ];
        foreach ($properties as $property => $value) {
            $reflection = new \ReflectionProperty(Rule::class, $property);
            $reflection->setAccessible(true);
            $reflection->setValue($rule, $value);
        }

        return $rule;
    }

    /**
     * Enabled category, without virtual root.
     *
     * @param int    $categoryId Category id.
     * @param string $path       Category path.
     * @param bool   $isVirtual  Whether the category is virtual.
     *
     * @return Category
     */
    private function getCategory(int $categoryId, string $path, bool $isVirtual): Category
    {
        $category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $category->method('getId')->willReturn($categoryId);
        $category->setData([
            'path'                => $path,
            'name'                => 'Category ' . $categoryId,
            'is_active'           => true,
            'is_virtual_category' => $isVirtual,
            'store_id'            => 1,
        ]);

        return $category;
    }
}
