<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Test\Unit\Model\Rule;

use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryDependencyCollector;

/**
 * Unit test for {@see QueryDependencyCollector}.
 *
 * Covered scenarios:
 * - Dependencies recorded outside of any build are ignored
 * - Dependencies of a nested build are propagated to the enclosing builds, however deep the chain is
 * - Dependencies of sibling builds do not leak into each other
 * - The earliest expiration date is propagated to the enclosing builds
 * - The context dependencies are propagated to the enclosing builds
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class QueryDependencyCollectorTest extends TestCase
{
    /**
     * @var QueryDependencyCollector
     */
    private $collector;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->collector = new QueryDependencyCollector();
    }

    /**
     * Test that dependencies recorded outside of any build are ignored.
     *
     * @return void
     */
    public function testNoBuild(): void
    {
        $this->collector->addCacheTags(['tag_a']);
        $this->collector->setExpiration(time());
        $this->assertNull($this->collector->getExpiration());
        $this->assertSame([], $this->collector->stop());

        $this->collector->start();
        $this->assertSame([], $this->collector->stop());
    }

    /**
     * Test the propagation of dependencies through a chain of three nested builds.
     *
     * @return void
     */
    public function testNestedBuildsPropagation(): void
    {
        $this->collector->start();
        $this->collector->addCacheTags(['tag_a']);

        $this->collector->start();
        $this->collector->addCacheTags(['tag_b']);

        $this->collector->start();
        $this->collector->addCacheTags(['tag_c', 'tag_d']);
        $this->assertTrue($this->collector->isCacheable());
        $this->assertSame(['tag_c', 'tag_d'], $this->collector->stop());

        $this->assertSame(['tag_b', 'tag_c', 'tag_d'], $this->collector->stop());
        $this->assertSame(['tag_a', 'tag_b', 'tag_c', 'tag_d'], $this->collector->stop());
    }

    /**
     * Test that sibling builds do not share their dependencies, while their parent gets both.
     *
     * @return void
     */
    public function testSiblingBuildsIsolation(): void
    {
        $this->collector->start();
        $this->collector->addCacheTags(['tag_a']);

        $this->collector->start();
        $this->collector->addCacheTags(['tag_b', 'tag_c']);
        $this->assertSame(['tag_b', 'tag_c'], $this->collector->stop());

        $this->collector->start();
        $this->collector->addCacheTags(['tag_d']);
        $this->assertSame(['tag_d'], $this->collector->stop());

        $this->assertSame(['tag_a', 'tag_b', 'tag_c', 'tag_d'], $this->collector->stop());
    }

    /**
     * Test that the earliest expiration date of the sub-queries is propagated to the enclosing builds.
     *
     * @return void
     */
    public function testExpirationPropagation(): void
    {
        $now = time();

        $this->collector->start();
        $this->assertNull($this->collector->getExpiration(), 'A query not built from the current date does not expire.');

        $this->collector->start();
        $this->collector->setExpiration($now + 7200);
        $this->collector->setExpiration(null);
        $this->collector->stop();

        $this->collector->start();
        $this->collector->setExpiration($now + 3600);
        $this->collector->stop();

        $this->assertSame($now + 3600, $this->collector->getExpiration(), 'The earliest expiration date is kept.');
        $this->collector->stop();
    }

    /**
     * Test that the context dependencies of a sub-query are propagated to the enclosing builds only.
     *
     * @return void
     */
    public function testContextDependencyPropagation(): void
    {
        $this->collector->start();

        $this->collector->start();
        $this->assertSame([], $this->collector->getContextDependencies());
        $this->collector->stop();
        $this->assertSame([], $this->collector->getContextDependencies(), 'A context free sub-query changes nothing.');

        $this->collector->start();
        $this->collector->addContextDependencies(['retailer', 'customer_group']);
        $this->collector->addContextDependencies(['customer_group']);
        $this->assertSame(['customer_group', 'retailer'], $this->collector->getContextDependencies(), 'Unique, sorted.');
        $this->collector->stop();
        $this->assertSame(
            ['customer_group', 'retailer'],
            $this->collector->getContextDependencies(),
            'The context dependencies of a sub-query are propagated.'
        );

        $this->collector->start();
        $this->assertSame([], $this->collector->getContextDependencies(), 'A sibling build is not impacted.');
        $this->collector->stop();

        $this->collector->stop();

        $this->collector->addContextDependencies(['customer_group']);
        $this->assertSame([], $this->collector->getContextDependencies(), 'Ignored out of any build.');
    }
}
