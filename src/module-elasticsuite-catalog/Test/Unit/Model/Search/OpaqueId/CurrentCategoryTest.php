<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Test\Unit\Model\Search\OpaqueId;

use Magento\Catalog\Api\Data\CategoryInterface;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalog\Model\Search\OpaqueId\CurrentCategory;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;

/**
 * Current category X-Opaque-Id part provider unit tests.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class CurrentCategoryTest extends TestCase
{
    /**
     * Test the part when the search context has a current category.
     *
     * @return void
     */
    public function testWithCurrentCategory(): void
    {
        $category = $this->createMock(CategoryInterface::class);
        $category->method('getId')->willReturn(4);
        $searchContext = $this->createMock(ContextInterface::class);
        $searchContext->method('getCurrentCategory')->willReturn($category);

        $this->assertEquals(['cat' => 4], (new CurrentCategory($searchContext))->getParts());
    }

    /**
     * Test the part when the search context has no current category.
     *
     * @return void
     */
    public function testWithoutCurrentCategory(): void
    {
        $searchContext = $this->createMock(ContextInterface::class);
        $searchContext->method('getCurrentCategory')->willReturn(null);

        $this->assertEquals(['cat' => null], (new CurrentCategory($searchContext))->getParts());
    }
}
