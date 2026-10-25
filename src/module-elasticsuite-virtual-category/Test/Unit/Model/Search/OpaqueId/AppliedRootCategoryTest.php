<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Model\Search\OpaqueId;

use Magento\Catalog\Model\Category;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteVirtualCategory\Model\Search\OpaqueId\AppliedRootCategory;
use Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root;

/**
 * Applied virtual root category X-Opaque-Id part provider unit tests.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class AppliedRootCategoryTest extends TestCase
{
    /**
     * Test the part when a virtual root category is applied.
     *
     * @return void
     */
    public function testWithAppliedRootCategory(): void
    {
        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn(49);
        $virtualCategoryRoot = $this->createMock(Root::class);
        $virtualCategoryRoot->method('getAppliedRootCategory')->willReturn($category);

        $this->assertEquals(['vcat' => 49], (new AppliedRootCategory($virtualCategoryRoot))->getParts());
    }

    /**
     * Test the part when no virtual root category is applied.
     *
     * @return void
     */
    public function testWithoutAppliedRootCategory(): void
    {
        $virtualCategoryRoot = $this->createMock(Root::class);
        $virtualCategoryRoot->method('getAppliedRootCategory')->willReturn(null);

        $this->assertEquals(['vcat' => null], (new AppliedRootCategory($virtualCategoryRoot))->getParts());
    }
}
