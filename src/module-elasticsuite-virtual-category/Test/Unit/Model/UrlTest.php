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
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory as CategoryCollectionFactory;
use Smile\ElasticsuiteVirtualCategory\Model\Url;
use Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root as VirtualCategoryRoot;

/**
 * Unit test for {@see Url}.
 *
 * Covers the extraction of the original category request path from a request path expressed under
 * a virtual category subtree.
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
     * @param VirtualCategoryRoot $virtualCategoryRoot Virtual category root model.
     *
     * @return Url
     */
    private function getUrlModel(VirtualCategoryRoot $virtualCategoryRoot): Url
    {
        return new Url(
            $this->createMock(ScopeConfigInterface::class),
            $this->createMock(StoreManagerInterface::class),
            $this->createMock(CategoryCollectionFactory::class),
            $this->createMock(UrlFinderInterface::class),
            $this->createMock(UrlInterface::class),
            $virtualCategoryRoot
        );
    }
}
