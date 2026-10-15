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
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogRule\Api\Rule\QueryContextResolverInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Combine;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\ProductFactory as ProductConditionFactory;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteCore\Cache\Type\Elasticsuite as ElasticsuiteCache;
use Smile\ElasticsuiteCore\Search\Request\Query\Boolean;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\Query\Term;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteVirtualCategory\Helper\Config;
use Smile\ElasticsuiteVirtualCategory\Model\Rule;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product as ProductCondition;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\DependencyCollector;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\RootCategoryProvider;

/**
 * Unit test of the cache decisions of {@see Rule::getCategorySearchQuery()} depending on the categories being built.
 *
 * Categories, in the store 1 whose root category is 2:
 * - X (10), virtual: category_ids [Y] AND discount
 * - Y (20), virtual: category_ids [X] OR category_ids [3]
 * - 3, standard
 * - R (30), virtual: category_ids [V]
 * - V (40), virtual: discount, with R as virtual root
 *
 * Each "process" has its own rule model, dependency collector and query cache, sharing the same cache storage.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class RuleBuildContextTest extends TestCase
{
    private const X = 10;

    private const Y = 20;

    private const STANDARD = 3;

    private const R = 30;

    private const V = 40;

    /**
     * @var array Shared cache storage: cache key => ['data' => serialized entry, 'tags' => tags].
     */
    private $storage = [];

    /**
     * @var Category[] Categories, by id.
     */
    private $categories = [];

    /**
     * @var int[] Number of evaluations of the virtual rule of each category, by category id.
     */
    private $ruleEvaluations = [];

    /**
     * @var int[] Number of loads of each category, by category id.
     */
    private $categoryLoads = [];

    /**
     * The query of a category does not depend on the order categories are built in: a query built while another
     * category was being built (and so left out of it) is neither cached nor reused.
     *
     * @return void
     */
    public function testQueriesDoNotDependOnTheBuildOrder(): void
    {
        $expectedX = $this->buildAlone(self::X);
        $expectedY = $this->buildAlone(self::Y);

        // Same process.
        $rule = $this->createRule();
        $this->assertEquals($expectedX, $rule->getCategorySearchQuery(self::X), 'X built first.');
        $this->assertEquals($expectedY, $rule->getCategorySearchQuery(self::Y), 'Y built after X.');

        $this->storage = [];
        $rule          = $this->createRule();
        $this->assertEquals($expectedY, $rule->getCategorySearchQuery(self::Y), 'Y built first.');
        $this->assertEquals($expectedX, $rule->getCategorySearchQuery(self::X), 'X built after Y.');

        // Another process, using the shared cache filled by the first one.
        $this->storage = [];
        $this->createRule()->getCategorySearchQuery(self::X);
        $this->assertEquals($expectedY, $this->createRule()->getCategorySearchQuery(self::Y), 'Y after X was cached.');

        $this->storage = [];
        $this->createRule()->getCategorySearchQuery(self::Y);
        $this->assertEquals($expectedX, $this->createRule()->getCategorySearchQuery(self::X), 'X after Y was cached.');
    }

    /**
     * The query of Y built while X is being built leaves X out: it is not cached, unlike the query of X itself.
     *
     * @return void
     */
    public function testTruncatedQueryIsNotCached(): void
    {
        $this->createRule()->getCategorySearchQuery(self::X);

        $this->assertNotEmpty($this->getCacheKeys(self::X));
        $this->assertEmpty($this->getCacheKeys(self::Y));
    }

    /**
     * The cached query of Y embeds the query of X: it is rebuilt while building X instead of being reused, since X
     * has to be left out of it.
     *
     * @return void
     */
    public function testCachedQueryEmbeddingACategoryBeingBuiltIsRebuilt(): void
    {
        $this->createRule()->getCategorySearchQuery(self::Y);
        $this->assertNotEmpty($this->getCacheKeys(self::Y));

        $this->ruleEvaluations = [];
        $this->createRule()->getCategorySearchQuery(self::X);

        $this->assertSame(1, $this->ruleEvaluations[self::Y] ?? 0, 'The cached query of Y is not reused.');
    }

    /**
     * V is left out of the query of its virtual root R the same way whether its query is built or served from the
     * cache, and in the latter case without loading V.
     *
     * @return void
     */
    public function testCachedQueryLeftOutForItsVirtualRoot(): void
    {
        $expectedR = $this->buildAlone(self::R);
        $this->assertSame([], $expectedR->getShould(), 'V is left out of R.');

        $this->createRule()->getCategorySearchQuery(self::V);
        $this->assertNotEmpty($this->getCacheKeys(self::V));

        $this->categoryLoads = [];
        $this->assertEquals($expectedR, $this->createRule()->getCategorySearchQuery(self::R));
        $this->assertSame(0, $this->categoryLoads[self::V] ?? 0, 'V is left out from its cached query.');
    }

    /**
     * The query of a category whose rule is a draft (eg. a preview) is neither read from nor written to the shared
     * cache.
     *
     * @return void
     */
    public function testDraftRuleSkipsTheSharedCache(): void
    {
        $this->createRule()->getCategorySearchQuery(self::X);
        $storage = $this->storage;

        $this->ruleEvaluations = [];
        $rule                  = $this->createRule();
        $this->categories[self::X]->setData('has_draft_virtual_rule', true);
        $rule->getCategorySearchQuery($this->categories[self::X]);

        $this->assertSame(1, $this->ruleEvaluations[self::X] ?? 0, 'The shared cache entry is not read.');
        $this->assertSame($storage, $this->storage, 'The shared cache is not written.');
    }

    /**
     * Build the query of a category in a new process, with an empty cache.
     *
     * @param int $categoryId Category id.
     *
     * @return QueryInterface|null
     */
    private function buildAlone(int $categoryId): ?QueryInterface
    {
        $this->storage = [];
        $query         = $this->createRule()->getCategorySearchQuery($categoryId);
        $this->storage = [];

        return $query;
    }

    /**
     * Keys of the shared cache entries of a category query.
     *
     * @param int $categoryId Category id.
     *
     * @return string[]
     */
    private function getCacheKeys(int $categoryId): array
    {
        return array_values(
            array_filter(
                array_keys($this->storage),
                function ($cacheKey) use ($categoryId) {
                    return strpos($cacheKey, 'getCategorySearchQuery|1|' . $categoryId . '|') === 0;
                }
            )
        );
    }

    /**
     * Rule model of a new process: its own dependency collector and query cache, sharing the cache storage.
     *
     * @return Rule
     */
    private function createRule(): Rule
    {
        $dependencyCollector = new DependencyCollector();
        $queryFactory        = $this->getQueryFactory();

        $config = $this->createMock(Config::class);
        $config->method('getVirtualRulesExpansionBoundaries')->willReturn([]);

        /** @var Rule|MockObject $rule */
        $rule = $this->getMockBuilder(Rule::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $rule->setData('store_id', 1);

        $this->categories = [];
        foreach ([self::X, self::Y, self::STANDARD, self::R, self::V] as $categoryId) {
            $this->categories[$categoryId] = $this->getCategory($categoryId);
        }
        $discount = new Term(true, 'price.is_discount');
        $this->setVirtualRule(self::X, $rule, $dependencyCollector, $queryFactory, 'must', [self::Y], [$discount]);
        $this->setVirtualRule(self::Y, $rule, $dependencyCollector, $queryFactory, 'should', [self::X, self::STANDARD]);
        $this->setVirtualRule(self::R, $rule, $dependencyCollector, $queryFactory, 'should', [self::V]);
        $this->setVirtualRule(self::V, $rule, $dependencyCollector, $queryFactory, 'must', [], [$discount]);
        $this->categories[self::V]->setData('virtual_category_root', self::R);

        $properties = [
            'config'                   => $config,
            'storeManager'             => $this->createMock(StoreManagerInterface::class),
            'categoryFactory'          => $this->getCategoryFactory(),
            'productConditionsFactory' => $this->getProductConditionFactory(),
            'queryBuilder'             => $this->getQueryBuilder(),
            'queryFactory'             => $queryFactory,
            'queryCache'               => new QueryCache($this->getSharedCache(), $this->getQueryContextResolver()),
            'dependencyCollector'      => $dependencyCollector,
            'rootCategoryProvider'     => $this->getRootCategoryProvider(),
        ];
        foreach ($properties as $property => $value) {
            $reflection = new \ReflectionProperty(Rule::class, $property);
            $reflection->setAccessible(true);
            $reflection->setValue($rule, $value);
        }

        return $rule;
    }

    /**
     * Category of the store 1, under its root category 2, without children.
     *
     * @param int $categoryId Category id.
     *
     * @return Category|MockObject
     */
    private function getCategory(int $categoryId): Category
    {
        $category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'hasChildren'])
            ->getMock();
        $category->method('getId')->willReturn($categoryId);
        $category->method('hasChildren')->willReturn(false);
        $category->setData(
            [
                'store_id'            => 1,
                'path'                => '1/2/' . $categoryId,
                'level'               => 2,
                'name'                => 'Category ' . $categoryId,
                'is_active'           => true,
                'is_virtual_category' => false,
            ]
        );

        return $category;
    }

    /**
     * Make a category virtual, with a rule combining category_ids conditions on the given categories (one condition
     * per category) and other conditions.
     *
     * @param int                 $categoryId          Category id.
     * @param Rule                $rule                Rule model building the category queries.
     * @param DependencyCollector $dependencyCollector Dependency collector of the process.
     * @param QueryFactory        $queryFactory        Query factory.
     * @param string              $clause              Boolean clause combining the conditions (must or should).
     * @param int[]               $referencedIds       Categories referenced by the category_ids conditions.
     * @param QueryInterface[]    $otherQueries        Queries of the other conditions of the rule.
     *
     * @return void
     */
    private function setVirtualRule(
        int $categoryId,
        Rule $rule,
        DependencyCollector $dependencyCollector,
        QueryFactory $queryFactory,
        string $clause,
        array $referencedIds,
        array $otherQueries = []
    ): void {
        $config = $this->createMock(Config::class);
        $config->method('getVirtualRulesExpansionBoundaries')->willReturn([]);

        $conditions = [];
        foreach ($referencedIds as $referencedId) {
            $condition = $this->getMockBuilder(ProductCondition::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getValue'])
                ->getMock();
            $condition->method('getValue')->willReturn((string) $referencedId);
            $condition->setData(['attribute' => 'category_ids', 'operator' => '()', 'rule' => $rule]);
            $this->setProperty(ProductCondition::class, $condition, 'queryFactory', $queryFactory);
            $this->setProperty(ProductCondition::class, $condition, 'virtualCategoryConfig', $config);
            $this->setProperty(ProductCondition::class, $condition, 'dependencyCollector', $dependencyCollector);
            $conditions[] = $condition;
        }

        $combine = $this->createMock(Combine::class);
        $combine->method('getSearchQuery')->willReturnCallback(
            function ($excludedCategories) use ($categoryId, $conditions, $clause, $otherQueries) {
                $this->ruleEvaluations[$categoryId] = ($this->ruleEvaluations[$categoryId] ?? 0) + 1;
                $queries = [];
                foreach ($conditions as $condition) {
                    $method = new \ReflectionMethod(ProductCondition::class, 'getCategorySearchQuery');
                    $method->setAccessible(true);
                    $queries[] = $method->invoke($condition, $excludedCategories);
                }

                return $this->createBoolQuery($clause, array_merge(array_values(array_filter($queries)), $otherQueries));
            }
        );
        $virtualRule = $this->createMock(Rule::class);
        $virtualRule->method('getConditions')->willReturn($combine);

        $this->categories[$categoryId]->addData(['is_virtual_category' => true, 'virtual_rule' => $virtualRule]);
    }

    /**
     * Boolean query with a single clause.
     *
     * @param string           $clause  Clause (must, should or mustNot).
     * @param QueryInterface[] $queries Clause queries.
     *
     * @return Boolean
     */
    private function createBoolQuery(string $clause, array $queries): Boolean
    {
        return new Boolean(
            $clause === 'must' ? $queries : [],
            $clause === 'should' ? $queries : [],
            $clause === 'mustNot' ? $queries : []
        );
    }

    /**
     * Query factory building actual queries, so that queries can be compared.
     *
     * @return QueryFactory|MockObject
     */
    private function getQueryFactory(): QueryFactory
    {
        $queryFactory = $this->createMock(QueryFactory::class);
        $queryFactory->method('create')->willReturnCallback(
            function ($type, $params) {
                $this->assertSame(QueryInterface::TYPE_BOOL, $type);

                return new Boolean(
                    $params['must'] ?? [],
                    $params['should'] ?? [],
                    $params['mustNot'] ?? [],
                    1,
                    null,
                    QueryInterface::DEFAULT_BOOST_VALUE,
                    $params['cached'] ?? false
                );
            }
        );

        return $queryFactory;
    }

    /**
     * Query builder of the standard categories queries: a term query on the category ids.
     *
     * @return QueryBuilder|MockObject
     */
    private function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getSearchQuery')->willReturnCallback(
            function ($condition) {
                return new Term(implode(',', $condition->getData('value')), 'category.category_id');
            }
        );

        return $queryBuilder;
    }

    /**
     * Factory of the conditions of the standard categories queries.
     *
     * @return ProductConditionFactory|MockObject
     */
    private function getProductConditionFactory(): ProductConditionFactory
    {
        $productConditionFactory = $this->createMock(ProductConditionFactory::class);
        $productConditionFactory->method('create')->willReturnCallback(
            function ($params) {
                $condition = $this->getMockBuilder(ProductCondition::class)
                    ->disableOriginalConstructor()
                    ->onlyMethods([])
                    ->getMock();
                $condition->setData($params['data']);

                return $condition;
            }
        );

        return $productConditionFactory;
    }

    /**
     * Category factory loading the categories of the test.
     *
     * @return CategoryFactory|MockObject
     */
    private function getCategoryFactory(): CategoryFactory
    {
        $categoryFactory = $this->createMock(CategoryFactory::class);
        $categoryFactory->method('create')->willReturnCallback(
            function () {
                $loader = $this->getMockBuilder(Category::class)
                    ->disableOriginalConstructor()
                    ->onlyMethods(['setStoreId', 'load'])
                    ->getMock();
                $loader->method('setStoreId')->willReturnSelf();
                $loader->method('load')->willReturnCallback(
                    function ($categoryId) {
                        $this->categoryLoads[$categoryId] = ($this->categoryLoads[$categoryId] ?? 0) + 1;

                        return $this->categories[(int) $categoryId];
                    }
                );

                return $loader;
            }
        );

        return $categoryFactory;
    }

    /**
     * Root categories provider serving the categories of the test.
     *
     * @return RootCategoryProvider|MockObject
     */
    private function getRootCategoryProvider(): RootCategoryProvider
    {
        $rootCategoryProvider = $this->createMock(RootCategoryProvider::class);
        $rootCategoryProvider->method('get')->willReturnCallback(
            function ($categoryId) {
                return $this->categories[(int) $categoryId];
            }
        );

        return $rootCategoryProvider;
    }

    /**
     * Shared cache backed by the storage of the test.
     *
     * @return ElasticsuiteCache|MockObject
     */
    private function getSharedCache(): ElasticsuiteCache
    {
        $sharedCache = $this->createMock(ElasticsuiteCache::class);
        $sharedCache->method('load')->willReturnCallback(
            function ($cacheKey) {
                return $this->storage[$cacheKey]['data'] ?? false;
            }
        );
        $sharedCache->method('save')->willReturnCallback(
            function ($data, $cacheKey, $tags) {
                $this->storage[$cacheKey] = ['data' => $data, 'tags' => $tags];

                return true;
            }
        );

        return $sharedCache;
    }

    /**
     * Query context resolver: none of the queries of the test depends on a context.
     *
     * @return QueryContextResolverInterface|MockObject
     */
    private function getQueryContextResolver(): QueryContextResolverInterface
    {
        $queryContextResolver = $this->createMock(QueryContextResolverInterface::class);
        $queryContextResolver->method('getValues')->willReturn([]);

        return $queryContextResolver;
    }

    /**
     * Set a private property of an object.
     *
     * @param string $class    Class declaring the property.
     * @param object $object   Object.
     * @param string $property Property name.
     * @param mixed  $value    Value.
     *
     * @return void
     */
    private function setProperty(string $class, $object, string $property, $value): void
    {
        $reflection = new \ReflectionProperty($class, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
