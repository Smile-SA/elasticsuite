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
}
