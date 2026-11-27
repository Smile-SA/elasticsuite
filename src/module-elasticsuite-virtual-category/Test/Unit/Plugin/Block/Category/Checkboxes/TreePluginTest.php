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

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Plugin\Block\Category\Checkboxes;

use Magento\Catalog\Block\Adminhtml\Category\Checkboxes\Tree;
use Magento\Framework\Data\Tree\Node;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteVirtualCategory\Plugin\Block\Category\Checkboxes\TreePlugin;

/**
 * Unit test for {@see TreePlugin}.
 *
 * Covered scenarios:
 * - The initial tree with selected categories registers a root built from their paths
 * - The paths are loaded once per block, and the cached category collection is released
 * - Lazy-loading a branch does not change the root
 * - The initial tree without selected categories does not change the root
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class TreePluginTest extends TestCase
{
    /**
     * Test that the initial tree with selected categories registers a root built from their paths.
     *
     * @return void
     */
    public function testInitialTreeLoadsSelectedPaths(): void
    {
        $tree = $this->getTree([3182, 5315]);
        $tree->expects($this->once())->method('getRootByIds')->with([3182, 5315]);

        $this->assertNull((new TreePlugin())->beforeGetRoot($tree));
    }

    /**
     * Test that the paths are loaded once per block, whatever the number of getRoot() calls,
     * and that the cached category collection is released after loading them.
     *
     * @return void
     */
    public function testSelectedPathsAreLoadedOnce(): void
    {
        $tree = $this->getTree([3182, 5315]);
        $tree->expects($this->once())->method('getRootByIds')->with([3182, 5315]);
        $tree->expects($this->once())->method('unsetData')->with('category_collection');

        $plugin = new TreePlugin();
        $this->assertNull($plugin->beforeGetRoot($tree));
        $this->assertNull($plugin->beforeGetRoot($tree));
        $this->assertNull($plugin->beforeGetRoot($tree));
    }

    /**
     * Test that lazy-loading a branch does not change the root.
     *
     * @return void
     */
    public function testLazyLoadKeepsRoot(): void
    {
        $tree = $this->getTree([3182, 5315]);
        $tree->expects($this->never())->method('getRootByIds');

        $this->assertNull((new TreePlugin())->beforeGetRoot($tree, $this->createMock(Node::class)));
    }

    /**
     * Test that the initial tree without selected categories does not change the root.
     *
     * @return void
     */
    public function testNoSelectionKeepsRoot(): void
    {
        $tree = $this->getTree([]);
        $tree->expects($this->never())->method('getRootByIds');

        $this->assertNull((new TreePlugin())->beforeGetRoot($tree));
    }

    /**
     * Get a category checkboxes tree mock.
     *
     * @param int[] $categoryIds Selected category ids.
     *
     * @return Tree&MockObject
     */
    private function getTree(array $categoryIds): Tree
    {
        $data = [];
        $tree = $this->createMock(Tree::class);
        $tree->method('getCategoryIds')->willReturn($categoryIds);
        $tree->method('getData')->willReturnCallback(
            function ($key = '') use (&$data) {
                return $data[$key] ?? null;
            }
        );
        $tree->method('setData')->willReturnCallback(
            function ($key, $value = null) use (&$data, &$tree) {
                $data[$key] = $value;

                return $tree;
            }
        );

        return $tree;
    }
}
