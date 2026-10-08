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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\Collection as CategoryCollection;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory as CategoryCollectionFactory;
use Smile\ElasticsuiteVirtualCategory\Model\Url;
use Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root as VirtualCategoryRoot;

/**
 * Unit test for {@see Url}.
 *
 * Covers the extraction of the original category request path from a request path expressed under
 * a virtual category subtree, and the stripping of the category URL suffix.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class UrlTest extends TestCase
{
    /**
     * Test the original category request path computation.
     *
     * @dataProvider originalCategoryRequestPathDataProvider
     *
     * @param string      $requestPath     Category request path (as expressed under the virtual category subtree).
     * @param string|null $appliedRootPath URL path of the applied virtual root category (null: no applied root).
     * @param string|null $rootOriginPath  URL path of the virtual root origin category (null: store root category).
     * @param string      $expectedPath    Expected original category request path.
     *
     * @return void
     */
    public function testGetOriginalCategoryRequestPath(
        string $requestPath,
        ?string $appliedRootPath,
        ?string $rootOriginPath,
        string $expectedPath
    ): void {
        $virtualCategoryRoot = $this->createMock(VirtualCategoryRoot::class);
        $virtualCategoryRoot->method('getVirtualCategoryRoot')
            ->willReturn(($rootOriginPath !== null) ? $this->getCategoryMock($rootOriginPath) : null);

        $appliedRoot = ($appliedRootPath !== null) ? $this->getCategoryMock($appliedRootPath) : null;
        $urlModel    = $this->getUrlModel($virtualCategoryRoot);

        $this->assertSame($expectedPath, $urlModel->getOriginalCategoryRequestPath($requestPath, $appliedRoot));
    }

    /**
     * Data provider for testGetOriginalCategoryRequestPath.
     *
     * @return array
     */
    public function originalCategoryRequestPathDataProvider(): array
    {
        return [
            'no applied root' => ['gear/bags.html', null, null, 'gear/bags.html'],
            'empty applied root url path' => ['gear/bags.html', '', null, 'gear/bags.html'],
            'store root origin, sub-category sharing the virtual url key' => [
                'sales/gear/bags/sales.html', 'sales', null, 'gear/bags/sales.html',
            ],
            'store root origin, sub-category containing the virtual url key' => [
                'sales/gear/bags/no-sales.html', 'sales', null, 'gear/bags/no-sales.html',
            ],
            'store root origin with empty url path' => [
                'sales/gear/bags/sales.html', 'sales', '', 'gear/bags/sales.html',
            ],
            'non-root origin' => ['sales/dresses.html', 'sales', 'women', 'women/dresses.html'],
            'non-root origin, sub-category sharing the virtual url key' => [
                'sales/tops/sales.html', 'sales', 'women', 'women/tops/sales.html',
            ],
            'nested virtual category' => [
                'promo/sales/promo/sales.html', 'promo/sales', null, 'promo/sales.html',
            ],
            'applied root is only a partial first segment' => [
                'sales-archive/sales/foo.html', 'sales', null, 'sales-archive/sales/foo.html',
            ],
            'applied root not at the beginning' => [
                'gear/sales/foo.html', 'sales', null, 'gear/sales/foo.html',
            ],
        ];
    }

    /**
     * Test that only the trailing category URL suffix is stripped before looking up the category.
     *
     * @dataProvider categoryUrlSuffixDataProvider
     *
     * @param string      $categoryPath       Category request path.
     * @param string|null $suffix             Category URL suffix configuration.
     * @param string      $expectedLookupPath Expected url_path used to load the category.
     *
     * @return void
     */
    public function testGetCategoryRewriteStripsTrailingSuffixOnly(
        string $categoryPath,
        ?string $suffix,
        string $expectedLookupPath
    ): void {
        $lookupPath = null;

        $collection = $this->createMock(CategoryCollection::class);
        $collection->method('setStoreId')->willReturnSelf();
        $collection->method('addAttributeToFilter')->willReturnCallback(
            function ($attribute, $condition) use ($collection, &$lookupPath) {
                if ($attribute === 'url_path') {
                    $lookupPath = $condition['eq'];
                }

                return $collection;
            }
        );
        $collection->method('getFirstItem')->willReturn(new DataObject());

        $collectionFactory = $this->createMock(CategoryCollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->with(Url::XML_PATH_CATEGORY_URL_SUFFIX)->willReturn($suffix);

        $urlModel = $this->getUrlModel(
            $this->createMock(VirtualCategoryRoot::class),
            $scopeConfig,
            $collectionFactory
        );

        $this->assertNull($urlModel->getCategoryRewrite($categoryPath, 1));
        $this->assertSame($expectedLookupPath, $lookupPath);
    }

    /**
     * Data provider for testGetCategoryRewriteStripsTrailingSuffixOnly.
     *
     * @return array
     */
    public function categoryUrlSuffixDataProvider(): array
    {
        return [
            'html suffix' => ['gear/bags/sales.html', '.html', 'gear/bags/sales'],
            'no suffix configured' => ['gear/bags/sales', null, 'gear/bags/sales'],
            'empty suffix' => ['gear/bags/sales', '', 'gear/bags/sales'],
            'slash suffix already trimmed' => ['gear/bags/sales', '/', 'gear/bags/sales'],
            'suffix also present inside the path' => ['gear/bags-x/sales-x', '-x', 'gear/bags-x/sales'],
            'suffix absent from the path' => ['gear/bags/sales', '.html', 'gear/bags/sales'],
        ];
    }

    /**
     * Build a category mock with a given URL path.
     *
     * @param string $urlPath Category URL path.
     *
     * @return Category|MockObject
     */
    private function getCategoryMock(string $urlPath)
    {
        $category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->addMethods(['getUrlPath'])
            ->getMock();
        $category->method('getUrlPath')->willReturn($urlPath);

        return $category;
    }

    /**
     * Build the tested URL model.
     *
     * @param VirtualCategoryRoot            $virtualCategoryRoot Virtual category root model.
     * @param ScopeConfigInterface|null      $scopeConfig         Scope config.
     * @param CategoryCollectionFactory|null $collectionFactory   Category collection factory.
     *
     * @return Url
     */
    private function getUrlModel(
        VirtualCategoryRoot $virtualCategoryRoot,
        ?ScopeConfigInterface $scopeConfig = null,
        ?CategoryCollectionFactory $collectionFactory = null
    ): Url {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(1);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new Url(
            $scopeConfig ?? $this->createMock(ScopeConfigInterface::class),
            $storeManager,
            $collectionFactory ?? $this->createMock(CategoryCollectionFactory::class),
            $this->createMock(UrlFinderInterface::class),
            $this->createMock(UrlInterface::class),
            $virtualCategoryRoot
        );
    }
}
