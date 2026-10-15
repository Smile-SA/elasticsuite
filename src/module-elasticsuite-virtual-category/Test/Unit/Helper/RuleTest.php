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

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterfaceFactory;
use Magento\Catalog\Model\Category;
use Magento\Customer\Model\Session;
use Magento\Framework\App\CacheInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteVirtualCategory\Helper\Config;
use Smile\ElasticsuiteVirtualCategory\Helper\Rule as RuleHelper;
use Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\ReadHandler;
use Smile\ElasticsuiteVirtualCategory\Model\Rule;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\DependencyCollector;

/**
 * Unit test for {@see RuleHelper::loadUsingCache()}.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class RuleTest extends TestCase
{
    /**
     * Test that the category search query, cached by the rule model itself, is not cached a second time.
     *
     * @return void
     */
    public function testSelfCachedCallbackIsNotCachedAgain(): void
    {
        $query       = $this->createMock(QueryInterface::class);
        $virtualRule = $this->createMock(Rule::class);
        $category    = $this->getCategory($virtualRule);
        $virtualRule->expects($this->once())->method('getCategorySearchQuery')->with($category)->willReturn($query);

        $queryCache = $this->createMock(QueryCache::class);
        $queryCache->expects($this->never())->method('load');
        $queryCache->expects($this->never())->method('save');

        $this->assertSame($query, $this->getHelper($queryCache)->loadUsingCache($category, 'getCategorySearchQuery'));
    }

    /**
     * Test that the data of the other callbacks is computed once, then served from the query cache.
     *
     * @return void
     */
    public function testOtherCallbackIsCached(): void
    {
        $queries     = [51 => $this->createMock(QueryInterface::class)];
        $virtualRule = $this->createMock(Rule::class);
        $category    = $this->getCategory($virtualRule);
        $virtualRule->expects($this->once())->method('getSearchQueriesByChildren')->with($category)
            ->willReturn($queries);

        $storage    = [];
        $queryCache = $this->createMock(QueryCache::class);
        $queryCache->method('load')->willReturnCallback(
            function ($cacheKey) use (&$storage) {
                return $storage[$cacheKey] ?? false;
            }
        );
        $queryCache->expects($this->once())->method('save')->willReturnCallback(
            function ($cacheKey, $data) use (&$storage) {
                $storage[$cacheKey] = ['data' => $data];
            }
        );

        $helper = $this->getHelper($queryCache);
        $this->assertSame($queries, $helper->loadUsingCache($category, 'getSearchQueriesByChildren'));
        $this->assertSame($queries, $helper->loadUsingCache($category, 'getSearchQueriesByChildren'));
        $this->assertSame(['getSearchQueriesByChildren|1|12|0|0'], array_keys($storage));
    }

    /**
     * Helper under test.
     *
     * @param QueryCache $queryCache Query cache mock.
     *
     * @return RuleHelper
     */
    private function getHelper(QueryCache $queryCache): RuleHelper
    {
        return new RuleHelper(
            $this->createMock(CacheInterface::class),
            $this->createMock(Session::class),
            $this->createMock(ReadHandler::class),
            $this->createMock(CategoryRepositoryInterfaceFactory::class),
            $this->createMock(Config::class),
            $queryCache,
            new DependencyCollector()
        );
    }

    /**
     * Category 12 of the store 1, with the given virtual rule.
     *
     * @param Rule $virtualRule Virtual rule.
     *
     * @return Category|MockObject
     */
    private function getCategory(Rule $virtualRule): Category
    {
        $category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getStoreId', 'getCacheTags'])
            ->getMock();
        $category->method('getId')->willReturn(12);
        $category->method('getStoreId')->willReturn(1);
        $category->method('getCacheTags')->willReturn(['cat_c_12']);
        $category->setData('virtual_rule', $virtualRule);

        return $category;
    }
}
