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

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Plugin\Staging\Catalog;

use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\CacheCleaner;
use Smile\ElasticsuiteVirtualCategory\Plugin\Staging\Catalog\CategoryApplierPlugin;

/**
 * Unit test for {@see CategoryApplierPlugin}.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class CategoryApplierPluginTest extends TestCase
{
    /**
     * The entries depending on the updated categories or on their ancestors are cleaned, the result being unchanged.
     *
     * @return void
     */
    public function testUpdatedCategoriesAreCleanedWithTheirAncestors(): void
    {
        $cacheCleaner = $this->createMock(CacheCleaner::class);
        $cacheCleaner->expects($this->once())->method('cleanWithAncestors')->with([12, 34]);

        $result = (new CategoryApplierPlugin($cacheCleaner))->afterExecute(new \stdClass(), 'result', [12, 34]);

        $this->assertSame('result', $result);
    }
}
