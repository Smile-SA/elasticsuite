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
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteVirtualCategory\Helper\Config;
use Smile\ElasticsuiteVirtualCategory\Model\Rule;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product as ProductCondition;

/**
 * Unit test for the store root category handling of {@see Rule::getCategorySearchQuery()}.
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
        // Full queries, as built with all the virtual categories under them.
        $this->setLocalCache([self::STORE_ROOT_ID => $this->fullQuery, self::OTHER_ROOT_ID => $this->fullQuery]);
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
        $rule = $this->getRule(true);

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
        $this->assertSame($this->fullQuery, $this->getRule(false)->getCategorySearchQuery(self::STORE_ROOT_ID, [12]));
    }

    /**
     * The root category of another store tree is not the store root category.
     *
     * @return void
     */
    public function testOtherStoreRootIsExpanded(): void
    {
        $this->assertSame($this->fullQuery, $this->getRule(true)->getCategorySearchQuery(self::OTHER_ROOT_ID, [12]));
    }

    /**
     * Rule whose dependencies are mocked, in a store whose root category is 2.
     *
     * @param bool $storeRootAsStandardCategory Whether the store root category is used as a standard category.
     *
     * @return Rule
     */
    private function getRule(bool $storeRootAsStandardCategory): Rule
    {
        $config = $this->createMock(Config::class);
        $config->method('isStoreRootUsedAsStandardCategory')->willReturn($storeRootAsStandardCategory);

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
        $category->method('getId')->willReturn(self::STORE_ROOT_ID);
        $category->setData(['path' => '1/' . self::STORE_ROOT_ID, 'name' => 'Default Category']);
        $categoryFactory = $this->createMock(CategoryFactory::class);
        $categoryFactory->method('create')->willReturn($category);

        $productConditionFactory = $this->createMock(ProductConditionFactory::class);
        $productConditionFactory->method('create')->willReturn($this->createMock(ProductCondition::class));
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getSearchQuery')->willReturn($this->standardQuery);

        // The store root query is never cached.
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
