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

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Model\Rule;

use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\DependencyCollector;

/**
 * Unit test for {@see DependencyCollector}.
 *
 * Covered scenarios (the generic ones being covered by the parent class test):
 * - Categories are converted to cache tags, without duplicates
 * - Categories of a nested build are propagated to the enclosing builds, however deep the chain is
 * - Cache tags of a query loaded from the cache are propagated like the categories of a built query
 * - Truncations are propagated to the enclosing builds until resolved by the build of the excluded category
 * - Embedded categories are propagated to the enclosing builds, left out ones are not embedded
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class DependencyCollectorTest extends TestCase
{
    /**
     * @var DependencyCollector
     */
    private $collector;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->collector = new DependencyCollector();
    }

    /**
     * Test categories to cache tags conversion.
     *
     * @return void
     */
    public function testGetCacheTags(): void
    {
        $this->assertSame(['ES_VCAT_3', 'ES_VCAT_42'], $this->collector->getCacheTags([3, '42', 3]));
        $this->assertSame([], $this->collector->getCacheTags([]));
    }

    /**
     * Test the propagation of dependencies through a chain of queries: Sale (50) -> Men (30) -> Men Picks (31).
     *
     * @return void
     */
    public function testNestedBuildsPropagation(): void
    {
        $this->collector->start();
        $this->collector->addCategoryIds([50]);

        $this->collector->start();
        $this->collector->addCategoryIds([30]);

        $this->collector->start();
        $this->collector->addCategoryIds([31, 21]);
        $this->assertSame(['ES_VCAT_31', 'ES_VCAT_21'], $this->collector->stop());

        $this->assertSame(['ES_VCAT_30', 'ES_VCAT_31', 'ES_VCAT_21'], $this->collector->stop());
        $this->assertSame(['ES_VCAT_50', 'ES_VCAT_30', 'ES_VCAT_31', 'ES_VCAT_21'], $this->collector->stop());
    }

    /**
     * Test that the cache tags of a query loaded from the cache are propagated to the enclosing build.
     *
     * @return void
     */
    public function testCachedTagsPropagation(): void
    {
        $this->collector->start();
        $this->collector->addCategoryIds([50]);

        $this->collector->start();
        $this->collector->addCacheTags(['ES_VCAT_20', 'ES_VCAT_22']);
        $this->collector->stop();

        $this->assertSame(['ES_VCAT_50', 'ES_VCAT_20', 'ES_VCAT_22'], $this->collector->stop());
    }

    /**
     * Test the truncation of a cycle: X = category_ids [Y] AND red, Y = category_ids [X] OR Foo.
     * While building X, Y leaves X out: Y is truncated and must not be cached, X is complete and can be.
     *
     * @return void
     */
    public function testCycleTruncationResolvedAtExcludedCategoryBuild(): void
    {
        // Build of X.
        $this->collector->start();
        $this->assertFalse($this->collector->isTruncated());

        // Build of Y, which leaves X out.
        $this->collector->start();
        $this->collector->addTruncation([10]);
        $this->collector->resolveTruncation([20]);
        $this->assertTrue($this->collector->isTruncated(), 'Y left X out and must not be cached.');
        $this->assertFalse($this->collector->isCacheable());
        $this->collector->stop();

        $this->assertTrue($this->collector->isTruncated(), 'The truncation is propagated to the enclosing build.');
        $this->collector->resolveTruncation([10]);
        $this->assertFalse($this->collector->isTruncated(), 'X build resolves the truncation caused by X exclusion.');
        $this->assertTrue($this->collector->isCacheable());
        $this->collector->stop();
    }

    /**
     * Test that a truncation is propagated through the intermediate builds until the build of the excluded category.
     * A (static) -> V2 (virtual, references C) -> C (virtual, root B, child of A): C is left out because its root is
     * under A, which is being built. V2 is truncated, A is not.
     *
     * @return void
     */
    public function testTruncationPropagatedThroughIntermediateBuilds(): void
    {
        $this->collector->start();
        $this->collector->start();
        $this->collector->start();
        $this->collector->addTruncation([1]);
        $this->collector->stop();

        $this->collector->resolveTruncation([2]);
        $this->assertTrue($this->collector->isTruncated(), 'V2 is truncated by A exclusion.');
        $this->collector->stop();

        $this->collector->resolveTruncation([1]);
        $this->assertFalse($this->collector->isTruncated(), 'A is complete.');
        $this->collector->stop();
        $this->assertFalse($this->collector->isTruncated(), 'No build left.');
    }

    /**
     * Test the embedded categories of "Tops" (static, 13) with two subset subcategories using it as virtual root
     * ("Cotton" 14, "Wool" 15): both are left out of "Tops", so they are dependencies of it, but not embedded in it.
     *
     * @return void
     */
    public function testLeftOutCategoriesAreNotEmbedded(): void
    {
        // Build of "Tops".
        $this->collector->start();
        $this->collector->addCategoryIds([13]);

        foreach ([14, 15] as $subcategoryId) {
            $this->collector->start();
            $this->collector->addCategoryIds([$subcategoryId]);
            $this->collector->addEmbedded([$subcategoryId, 13]);
            // Left out, since its root ("Tops") is being built.
            $this->collector->discardEmbedded();
            $this->collector->stop();
        }

        $this->collector->addEmbedded([13]);
        $this->assertSame([13], $this->collector->getEmbedded(), 'Left out subset subcategories are not embedded.');
        $this->assertSame(['ES_VCAT_13', 'ES_VCAT_14', 'ES_VCAT_15'], $this->collector->stop(), 'They are dependencies.');
    }

    /**
     * Test that embedded categories are propagated to the enclosing builds, without leaking into sibling builds.
     *
     * @return void
     */
    public function testEmbeddedPropagation(): void
    {
        $this->collector->start();

        $this->collector->start();
        $this->collector->addEmbedded([20, 22]);
        $this->collector->stop();

        $this->collector->start();
        $this->assertSame([], $this->collector->getEmbedded(), 'A sibling build is not impacted.');
        $this->collector->addEmbedded([30]);
        $this->collector->stop();

        $this->assertSame([20, 22, 30], $this->collector->getEmbedded());
        $this->collector->stop();
    }
}
