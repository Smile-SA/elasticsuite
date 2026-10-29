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

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Model\Rule\Cycle;

use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\Cycle\Detector;

/**
 * Unit test for {@see Detector}.
 *
 * Tree used (1 is the global root, 2 the store root):
 * - 10 Women (static)
 *   - 11 Tops (static), with subset subcategories 12 "Cotton" and 13 "Red" (virtual, root 11)
 *   - 14 Women Picks (virtual): category_ids [30]
 *   - 15 Women Sale (virtual): category_ids [20]
 * - 20 Sale (virtual): category_ids [10]
 * - 30 Collection (virtual): category_ids [10, 12]
 * - 40 Self (virtual): category_ids [40]
 * - 50 New (virtual): root 2 (the store root: not used as virtual root)
 *
 * Covered scenarios:
 * - Subset subcategories (using their parent as virtual root) do not create cycles
 * - Cycles through a static parent and its virtual children are detected (Women > Picks > Collection > Women)
 * - Self references are cycles
 * - The root of the tree as virtual root and unknown references create no dependency
 * - Cycles can be restricted to the ones going through some categories, starting from them
 * - Groups of interdependent categories larger than the cycle path are reported with their size
 * - Physical categories (boundary categories, store roots) break the cycles going through them
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class DetectorTest extends TestCase
{
    /**
     * Test the cycles of the whole tree.
     *
     * @return void
     */
    public function testFindCycles(): void
    {
        $cycles = (new Detector())->findCycles($this->getCategories());

        $this->assertCount(2, $cycles);
        // Women relies on Women Picks and Women Sale, which rely on Collection and Sale, which rely on Women.
        $this->assertSame(['path' => [10, 14, 30, 10], 'size' => 5, 'members' => [10, 14, 15, 20, 30]], $cycles[0]);
        $this->assertSame(['path' => [40, 40], 'size' => 1, 'members' => [40]], $cycles[1]);
    }

    /**
     * Test that no dependency leads to a category used as a physical category by the other ones (eg. a boundary).
     *
     * @return void
     */
    public function testPhysicalCategoriesBreakCycles(): void
    {
        $categories = $this->getCategories();
        $categories[10]['physical'] = true;

        $this->assertSame(
            [['path' => [40, 40], 'size' => 1, 'members' => [40]]],
            (new Detector())->findCycles($categories),
            'Collection and Sale no longer rely on Women: only the self reference is left.'
        );
    }

    /**
     * Test the cycles going through some categories.
     *
     * @return void
     */
    public function testFindCyclesThrough(): void
    {
        $detector = new Detector();

        $this->assertSame(
            [['path' => [20, 10, 15, 20], 'size' => 5, 'members' => [10, 14, 15, 20, 30]]],
            $detector->findCycles($this->getCategories(), [20]),
            'The path starts from the requested category.'
        );
        $this->assertSame([], $detector->findCycles($this->getCategories(), [11, 12, 13, 50]), 'Not in a cycle.');
    }

    /**
     * Test that subset subcategories (using their parent as virtual root) are no cycle, even when referenced by
     * their parent ancestors.
     *
     * @return void
     */
    public function testSubsetSubcategoriesAreNotCycles(): void
    {
        $categories = $this->getCategories();
        unset($categories[14], $categories[15], $categories[40]);

        $this->assertSame([], (new Detector())->findCycles($categories));
    }

    /**
     * Categories of the test tree.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @return array
     */
    private function getCategories(): array
    {
        $category = function (array $path, bool $virtual = false, ?int $root = null, array $refs = []) {
            return ['path' => $path, 'virtual' => $virtual, 'root' => $root, 'refs' => $refs];
        };

        return [
            1  => $category([1]),
            2  => $category([1, 2]),
            10 => $category([1, 2, 10]),
            11 => $category([1, 2, 10, 11]),
            12 => $category([1, 2, 10, 11, 12], true, 11),
            13 => $category([1, 2, 10, 11, 13], true, 11),
            14 => $category([1, 2, 10, 14], true, null, [30]),
            15 => $category([1, 2, 10, 15], true, null, [20]),
            20 => $category([1, 2, 20], true, null, [10]),
            30 => $category([1, 2, 30], true, null, [10, 12, 999]),
            40 => $category([1, 2, 40], true, null, [40]),
            50 => $category([1, 2, 50], true, 2),
        ];
    }
}
