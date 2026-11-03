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
use Magento\Catalog\Model\CategoryFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\ProductFactory as ProductConditionFactory;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteVirtualCategory\Helper\Config;
use Smile\ElasticsuiteVirtualCategory\Model\Rule;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product as ProductCondition;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\DependencyCollector;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\RootCategoryProvider;

/**
 * Unit test for the store root category and the expansion boundaries handling of
 * {@see Rule::getCategorySearchQuery()}.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class RuleTest extends TestCase
{
    private const STORE_ROOT_ID = 2;

    private const OTHER_ROOT_ID = 58;

    private const BOUNDARY_ID = 3181;

    /**
     * Message of the exception thrown when the normal build path of a category query is reached, ie. when the
     * category is fully expanded instead of being reduced to its standard query.
     */
    private const EXPANDED = 'Expanded';

    /**
     * @var QueryInterface
     */
    private $standardQuery;

    /**
     * @var DependencyCollector|MockObject
     */
    private $dependencyCollector;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->standardQuery = $this->createMock(QueryInterface::class);
    }

    /**
     * The store root category is used as a standard category, on its own (eg. a GraphQL category filter) or used by
     * another category, without any cache.
     *
     * @return void
     */
    public function testStoreRootIsStandardCategory(): void
    {
        $rule = $this->getRule(self::STORE_ROOT_ID, true, true, true);

        $this->assertSame($this->standardQuery, $rule->getCategorySearchQuery(self::STORE_ROOT_ID));
        $this->assertSame($this->standardQuery, $rule->getCategorySearchQuery(self::STORE_ROOT_ID, [12]));
    }

    /**
     * The store root category is fully expanded when the setting is disabled.
     *
     * @return void
     */
    public function testStoreRootIsExpandedWhenDisabled(): void
    {
        $rule = $this->getRule(self::STORE_ROOT_ID, false, true, true);

        $this->expectExceptionMessage(self::EXPANDED);
        $rule->getCategorySearchQuery(self::STORE_ROOT_ID, [12]);
    }

    /**
     * The root category of another store tree is not the store root category.
     *
     * @return void
     */
    public function testOtherStoreRootIsExpanded(): void
    {
        $rule = $this->getRule(self::OTHER_ROOT_ID, true, true, true);

        $this->expectExceptionMessage(self::EXPANDED);
        $rule->getCategorySearchQuery(self::OTHER_ROOT_ID, [12]);
    }

    /**
     * An expansion boundary used by another category is reduced to its standard query, without any cache.
     *
     * @return void
     */
    public function testEmbeddedBoundaryIsNotExpanded(): void
    {
        $rule = $this->getRule(self::BOUNDARY_ID, true, true, true);

        $this->assertSame($this->standardQuery, $rule->getCategorySearchQuery(self::BOUNDARY_ID, [12]));
    }

    /**
     * An expansion boundary used by another category is a dependency of the query embedding it.
     *
     * @return void
     */
    public function testEmbeddedBoundaryIsADependency(): void
    {
        $rule = $this->getRule(self::BOUNDARY_ID, true, true, true);
        $this->dependencyCollector->expects($this->once())->method('addCategoryIds')->with([self::BOUNDARY_ID]);

        $rule->getCategorySearchQuery(self::BOUNDARY_ID, [12]);
    }

    /**
     * The query of an expansion boundary for its own page is unchanged.
     *
     * @return void
     */
    public function testTopLevelBoundaryIsExpanded(): void
    {
        $rule = $this->getRule(self::BOUNDARY_ID, true, true, true);

        $this->expectExceptionMessage(self::EXPANDED);
        $rule->getCategorySearchQuery(self::BOUNDARY_ID);
    }

    /**
     * A disabled expansion boundary used by another category is handled as any disabled category.
     *
     * @return void
     */
    public function testEmbeddedDisabledBoundary(): void
    {
        $rule = $this->getRule(self::BOUNDARY_ID, true, false, false);
        $this->assertNull($rule->getCategorySearchQuery(self::BOUNDARY_ID, [12]));

        $rule           = $this->getRule(self::BOUNDARY_ID, true, false, true);
        $noResultsQuery = $rule->getCategorySearchQuery(self::BOUNDARY_ID, [12]);
        $this->assertNotNull($noResultsQuery);
        $this->assertNotSame($this->standardQuery, $noResultsQuery);
    }

    /**
     * Rule whose dependencies are mocked, in a store whose root category is 2, with the category 3181 configured as an
     * expansion boundary.
     *
     * @param int  $categoryId                  Id of the category loaded by the rule.
     * @param bool $storeRootAsStandardCategory Whether the store root category is used as a standard category.
     * @param bool $isActive                    Whether the loaded category is enabled.
     * @param bool $forceZero                   Whether disabled categories force zero results.
     *
     * @return Rule
     */
    private function getRule(
        int $categoryId,
        bool $storeRootAsStandardCategory,
        bool $isActive,
        bool $forceZero
    ): Rule {
        $config = $this->createMock(Config::class);
        $config->method('isStoreRootUsedAsStandardCategory')->willReturn($storeRootAsStandardCategory);
        $config->method('getVirtualRulesExpansionBoundaries')->willReturn([self::BOUNDARY_ID]);
        $config->method('getVirtualRulesExpansionBoundariesCacheKey')->willReturn('b1');
        $config->method('isForceZeroResultsForDisabledCategoriesEnabled')->willReturn($forceZero);

        $store = $this->createMock(Store::class);
        $store->method('getRootCategoryId')->willReturn(self::STORE_ROOT_ID);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->with(1)->willReturn($store);

        $category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'getId', 'setStoreId'])
            ->getMock();
        $category->method('load')->willReturnSelf();
        $category->method('setStoreId')->willReturnSelf();
        $category->method('getId')->willReturn($categoryId);
        $category->setData([
            'is_active' => $isActive,
            'path'      => $categoryId === self::STORE_ROOT_ID ? '1/2' : '1/2/' . $categoryId,
            'name'      => 'Category ' . $categoryId,
        ]);
        $categoryFactory = $this->createMock(CategoryFactory::class);
        $categoryFactory->method('create')->willReturn($category);

        $productConditionFactory = $this->createMock(ProductConditionFactory::class);
        $productConditionFactory->method('create')->willReturn($this->createMock(ProductCondition::class));
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getSearchQuery')->willReturn($this->standardQuery);

        $queryFactory = $this->createMock(QueryFactory::class);
        $queryFactory->method('create')->willReturn($this->createMock(QueryInterface::class));

        // Reached when a category is fully expanded (the store root and boundary queries are built before it).
        $dependencyCollector = $this->createMock(DependencyCollector::class);
        $dependencyCollector->method('start')->willThrowException(new \LogicException(self::EXPANDED));
        $this->dependencyCollector = $dependencyCollector;

        $rootCategoryProvider = $this->createMock(RootCategoryProvider::class);
        $rootCategoryProvider->method('get')->willReturn($category);

        /** @var Rule|MockObject $rule */
        $rule = $this->getMockBuilder(Rule::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $rule->setData('store_id', 1);
        $properties = [
            'config'                   => $config,
            'storeManager'             => $storeManager,
            'categoryFactory'          => $categoryFactory,
            'productConditionsFactory' => $productConditionFactory,
            'queryBuilder'             => $queryBuilder,
            'queryFactory'             => $queryFactory,
            'dependencyCollector'      => $dependencyCollector,
            'rootCategoryProvider'     => $rootCategoryProvider,
        ];
        foreach ($properties as $property => $value) {
            $reflection = new \ReflectionProperty(Rule::class, $property);
            $reflection->setAccessible(true);
            $reflection->setValue($rule, $value);
        }

        return $rule;
    }
}
