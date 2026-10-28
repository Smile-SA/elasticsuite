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

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\CacheInterface;
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
#[AllowMockObjectsWithoutExpectations]
class RuleTest extends TestCase
{
    private const STORE_ROOT_ID = 2;

    private const OTHER_ROOT_ID = 58;

    private const BOUNDARY_ID = 3181;

    /**
     * @var QueryInterface
     */
    private $standardQuery;

    /**
     * @var QueryInterface
     */
    private $fullQuery;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->standardQuery = $this->createMock(QueryInterface::class);
        $this->fullQuery     = $this->createMock(QueryInterface::class);
        // Full queries, as built with all the virtual categories under them (eg. for the page of the boundary).
        $this->setLocalCache([
            self::STORE_ROOT_ID => $this->fullQuery,
            self::OTHER_ROOT_ID => $this->fullQuery,
            self::BOUNDARY_ID   => $this->fullQuery,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        $this->setLocalCache([]);
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

        $this->assertSame($this->fullQuery, $rule->getCategorySearchQuery(self::STORE_ROOT_ID, [12]));
    }

    /**
     * The root category of another store tree is not the store root category.
     *
     * @return void
     */
    public function testOtherStoreRootIsExpanded(): void
    {
        $rule = $this->getRule(self::OTHER_ROOT_ID, true, true, true);

        $this->assertSame($this->fullQuery, $rule->getCategorySearchQuery(self::OTHER_ROOT_ID, [12]));
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
     * The query of an expansion boundary for its own page is unchanged.
     *
     * @return void
     */
    public function testTopLevelBoundaryIsExpanded(): void
    {
        $rule = $this->getRule(self::BOUNDARY_ID, true, true, true);

        $this->assertSame($this->fullQuery, $rule->getCategorySearchQuery(self::BOUNDARY_ID));
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

        // The store root and boundary queries are never cached.
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->never())->method('save');

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
            'customerSession'          => $this->createMock(Session::class),
            'sharedCache'              => $cache,
        ];
        foreach ($properties as $property => $value) {
            $reflection = new \ReflectionProperty(Rule::class, $property);
            $reflection->setValue($rule, $value);
        }

        return $rule;
    }

    /**
     * Set the in-process cache of the category queries.
     *
     * @param array $queries Queries, by category id.
     *
     * @return void
     */
    private function setLocalCache(array $queries): void
    {
        $reflection = new \ReflectionProperty(Rule::class, 'localCache');
        $reflection->setValue(null, $queries);
    }
}
