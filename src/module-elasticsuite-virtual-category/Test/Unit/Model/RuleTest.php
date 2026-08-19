<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Customer\Model\Session;
use Magento\Framework\App\CacheInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product as ProductCondition;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\Boolean;
use Smile\ElasticsuiteCore\Search\Request\Query\Terms;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteVirtualCategory\Helper\Config;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Combine;
use Smile\ElasticsuiteVirtualCategory\Model\Rule;

/**
 * Virtual category rule unit tests.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 */
class RuleTest extends TestCase
{
    /**
     * @var CacheInterface|MockObject
     */
    private $sharedCache;

    /**
     * @var QueryBuilder|MockObject
     */
    private $queryBuilder;

    /**
     * @var MockObject[]
     */
    private $conditions = [];

    /**
     * Reset the rule static local cache, shared by every instance.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $localCache = new \ReflectionProperty(Rule::class, 'localCache');
        $localCache->setAccessible(true);
        $localCache->setValue(null, []);
    }

    /**
     * A query built while categories were excluded is context dependent: it must never be served
     * to a later context free call, which is the one a category page issues.
     *
     * @return void
     */
    public function testContextualQueryIsNotServedToAContextFreeCall()
    {
        $rule = $this->getRule();

        $contextual = $rule->getCategorySearchQuery(42, [1337]);
        $contextFree = $rule->getCategorySearchQuery(42, []);

        $this->assertInstanceOf(QueryInterface::class, $contextual);
        $this->assertInstanceOf(QueryInterface::class, $contextFree);
        $this->assertNotSame($contextual, $contextFree);
    }

    /**
     * Only the context free query is shared across requests: a contextual one would be served later
     * under a key that does not describe it.
     *
     * @return void
     */
    public function testOnlyTheContextFreeQueryIsStoredInTheSharedCache()
    {
        $saved = [];
        $rule = $this->getRule();
        $this->sharedCache->method('save')->willReturnCallback(
            function ($data, $key) use (&$saved) {
                $saved[] = $key;

                return true;
            }
        );

        $rule->getCategorySearchQuery(42, [1337]);
        $this->assertSame([], $saved);

        $rule->getCategorySearchQuery(42, []);
        $this->assertCount(1, $saved);
    }

    /**
     * A virtual category already expands its own descendants. Listing every descendant of the current
     * category duplicates whole subqueries, once per virtual ancestor.
     *
     * @return void
     */
    public function testVirtualDescendantsAreNotExpandedTwice()
    {
        $child = $this->getVirtualCategoryMock(100, '1/2/42/100');
        $grandChild = $this->getVirtualCategoryMock(200, '1/2/42/100/200');

        // The grand child is already covered by the child query, it must not be built again.
        $this->getConditionsOf($child)->expects($this->once())->method('getSearchQuery');
        $this->getConditionsOf($grandChild)->expects($this->never())->method('getSearchQuery');

        $rule = $this->getRule([$child, $grandChild]);
        $query = $rule->getCategorySearchQuery(42, []);

        $this->assertInstanceOf(Boolean::class, $query);
        $this->assertCount(2, $query->getShould());
    }

    /**
     * Build a virtual category mock, with its own rule conditions.
     *
     * @param int    $categoryId Category id.
     * @param string $path       Category path.
     *
     * @return Category|MockObject
     */
    private function getVirtualCategoryMock(int $categoryId, string $path)
    {
        $conditions = $this->getMockBuilder(Combine::class)->disableOriginalConstructor()
            ->onlyMethods(['getSearchQuery'])->getMock();
        $conditions->method('getSearchQuery')->willReturnCallback(
            function () use ($categoryId) {
                return new Terms([$categoryId], 'category.category_id');
            }
        );

        $virtualRule = $this->getMockBuilder(Rule::class)->disableOriginalConstructor()
            ->onlyMethods(['getConditions'])->getMock();
        $virtualRule->method('getConditions')->willReturn($conditions);

        $category = $this->getCategoryMock($categoryId, $path, true, false);
        $category->method('getVirtualRule')->willReturn($virtualRule);

        $this->conditions[$categoryId] = $conditions;

        return $category;
    }

    /**
     * Rule conditions of a mocked category.
     *
     * @param Category|MockObject $category Category.
     *
     * @return MockObject
     */
    private function getConditionsOf($category)
    {
        return $this->conditions[$category->getId()];
    }

    /**
     * Build a category mock.
     *
     * @param int    $categoryId  Category id.
     * @param string $path        Category path.
     * @param bool   $isVirtual   Whether the category is virtual.
     * @param bool   $hasChildren Whether the category has children.
     *
     * @return Category|MockObject
     */
    private function getCategoryMock(int $categoryId, string $path, bool $isVirtual, bool $hasChildren)
    {
        $category = $this->getMockBuilder(Category::class)->disableOriginalConstructor()
            ->addMethods(['getHasDraftVirtualRule', 'getIsVirtualCategory', 'getVirtualCategoryRoot', 'getVirtualRule'])
            ->onlyMethods(
                [
                    'getId', 'getIsActive', 'hasChildren', 'getPath', 'getPathIds', 'getName',
                    'getCacheTags', 'getStoreId', 'setStoreId', 'load',
                ]
            )
            ->getMock();

        $category->method('getId')->willReturn($categoryId);
        $category->method('getIsActive')->willReturn(true);
        $category->method('getIsVirtualCategory')->willReturn($isVirtual);
        $category->method('getVirtualCategoryRoot')->willReturn(null);
        $category->method('getHasDraftVirtualRule')->willReturn(false);
        $category->method('hasChildren')->willReturn($hasChildren);
        $category->method('getPath')->willReturn($path);
        $category->method('getPathIds')->willReturn(explode('/', $path));
        $category->method('getName')->willReturn('Category ' . $categoryId);
        $category->method('getCacheTags')->willReturn([]);
        $category->method('getStoreId')->willReturn(1);
        $category->method('setStoreId')->willReturnSelf();
        $category->method('load')->willReturnSelf();

        return $category;
    }

    /**
     * Build a rule with every collaborator stubbed, on a standard category.
     *
     * @param array $descendants Virtual descendants returned by the children collection.
     *
     * @return Rule
     */
    private function getRule(array $descendants = []): Rule
    {
        $this->sharedCache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $this->sharedCache->method('load')->willReturn(false);

        $this->queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()->getMock();
        // A distinct query instance per call, so the test can tell two builds apart.
        $this->queryBuilder->method('getSearchQuery')->willReturnCallback(
            function () {
                return new Terms([42], 'category.category_id');
            }
        );

        $customerSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $customerSession->method('getCustomerGroupId')->willReturn(0);

        $config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $config->method('isForceZeroResultsForDisabledCategoriesEnabled')->willReturn(false);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->getMock();

        $category = $this->getCategoryMock(42, '1/2/42', false, !empty($descendants));

        $collection = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'setStoreId', 'addIsActiveFilter', 'addPathFilter', 'addFieldToFilter',
                    'addAttributeToFilter', 'addAttributeToSelect', 'getSize', 'getIterator',
                ]
            )
            ->getMock();
        $fluentMethods = [
            'setStoreId', 'addIsActiveFilter', 'addPathFilter',
            'addFieldToFilter', 'addAttributeToFilter', 'addAttributeToSelect',
        ];
        foreach ($fluentMethods as $method) {
            $collection->method($method)->willReturnSelf();
        }
        $collection->method('getSize')->willReturn(count($descendants));
        $collection->method('getIterator')->willReturnCallback(
            function () use ($descendants) {
                return new \ArrayIterator($descendants);
            }
        );

        $rule = (new \ReflectionClass(Rule::class))->newInstanceWithoutConstructor();

        $properties = [
            'sharedCache'              => $this->sharedCache,
            'queryBuilder'             => $this->queryBuilder,
            'customerSession'          => $customerSession,
            'config'                   => $config,
            'storeManager'             => $storeManager,
            'categoryFactory'          => $this->getFactoryStub($category),
            'categoryCollectionFactory' => $this->getFactoryStub($collection),
            'productConditionsFactory' => $this->getFactoryStub(
                $this->getMockBuilder(ProductCondition::class)->disableOriginalConstructor()->getMock()
            ),
            'queryFactory'             => new class {
                /**
                 * @param string $type   Query type.
                 * @param array  $params Query parameters.
                 * @return Boolean
                 */
                public function create($type, $params = [])
                {
                    return new Boolean(
                        $params['must'] ?? [],
                        $params['should'] ?? [],
                        $params['mustNot'] ?? []
                    );
                }
            },
        ];
        foreach ($properties as $name => $value) {
            $property = new \ReflectionProperty(Rule::class, $name);
            $property->setAccessible(true);
            $property->setValue($rule, $value);
        }

        $data = new \ReflectionProperty(\Magento\Framework\DataObject::class, '_data');
        $data->setAccessible(true);
        $data->setValue($rule, ['store_id' => 1]);

        return $rule;
    }

    /**
     * A factory stub always returning the same instance.
     *
     * @param object $instance Instance returned by the factory.
     *
     * @return object
     */
    private function getFactoryStub($instance)
    {
        return new class ($instance) {
            /**
             * @param object $instance Instance returned by the factory.
             */
            public function __construct(private $instance)
            {
            }

            /**
             * @param array $data Unused creation data.
             * @return object
             */
            public function create($data = [])
            {
                return $this->instance;
            }
        };
    }
}
