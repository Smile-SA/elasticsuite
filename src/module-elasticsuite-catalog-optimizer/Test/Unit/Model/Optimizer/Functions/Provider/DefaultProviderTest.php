<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Test\Unit\Model\Optimizer\Functions\Provider;

use Magento\Framework\App\CacheInterface;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\ApplierInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\ProviderFactory as CollectionProviderFactory;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\ProviderInterface as CollectionProviderInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\CacheKeyProviderInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\Provider\DefaultProvider;
use Smile\ElasticsuiteCatalogRule\Api\Rule\QueryContextResolverInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryDependencyCollector;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Cache\Type\Elasticsuite as ElasticsuiteCache;

/**
 * Unit test for the caching of {@see DefaultProvider}.
 *
 * Covered scenarios:
 * - Functions not depending on any context are stored once and served to all contexts, tagged as optimizers
 * - Functions depending on a context (eg. "is discount" conditions) are stored per value of that context
 * - The dependencies recorded while building the functions are propagated to an enclosing build, even when served
 *   from the cache
 * - The cache lifetime is a maximum: the earliest expiration date recorded meanwhile wins
 * - Functions built in a non cacheable build are not stored
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class DefaultProviderTest extends TestCase
{
    /**
     * Base cache key of the functions of the container.
     */
    private const CACHE_KEY = 'optimizer_boost_function_catalog_view_container_1';

    /**
     * @var array Shared cache storage: cache key => ['data' => serialized entry, 'tags' => tags, 'lifetime' => lifetime].
     */
    private $storage = [];

    /**
     * @var string[] Current context values, by code.
     */
    private $context = ['customer_group' => '0'];

    /**
     * @var integer Number of functions built by the applier.
     */
    private $builds = 0;

    /**
     * @var QueryDependencyCollector Collector of the provider being tested.
     */
    private $currentCollector;

    /**
     * Test that functions not depending on any context are stored once and served to all contexts.
     *
     * @return void
     */
    public function testContextIndependentFunctions(): void
    {
        $applier = $this->getApplier(['ES_VCAT_12'], [], null);

        $this->assertSame([10 => 'function'], $this->getProvider($applier)->getFunctions($this->getContainer()));
        $this->context['customer_group'] = '1';
        // Another process, sharing the cache.
        $this->assertSame([10 => 'function'], $this->getProvider($applier)->getFunctions($this->getContainer()));

        $this->assertSame(1, $this->builds);
        $this->assertSame([self::CACHE_KEY], array_keys($this->storage));
        $this->assertEqualsCanonicalizing([Optimizer::CACHE_TAG, 'ES_VCAT_12'], $this->storage[self::CACHE_KEY]['tags']);
    }

    /**
     * Test that functions depending on a context are stored per value of that context.
     *
     * @return void
     */
    public function testContextDependentFunctions(): void
    {
        $applier = $this->getApplier([], ['customer_group'], null);

        $this->getProvider($applier)->getFunctions($this->getContainer());
        $this->context['customer_group'] = '1';
        $this->getProvider($applier)->getFunctions($this->getContainer());
        $this->context['customer_group'] = '0';
        $this->getProvider($applier)->getFunctions($this->getContainer());

        $this->assertSame(2, $this->builds);
        $this->assertEqualsCanonicalizing(
            [self::CACHE_KEY, self::CACHE_KEY . '|customer_group=0', self::CACHE_KEY . '|customer_group=1'],
            array_keys($this->storage)
        );
    }

    /**
     * Test that the dependencies of the functions are propagated to an enclosing build, built or served from the cache.
     *
     * @return void
     */
    public function testDependenciesPropagatedToEnclosingBuild(): void
    {
        $expiresAt = time() + 60;
        $applier   = $this->getApplier(['ES_VCAT_12'], ['customer_group'], $expiresAt);

        foreach (['built' => 1, 'cached' => 1] as $case => $expectedBuilds) {
            $collector = new QueryDependencyCollector();
            $collector->start();
            $this->getProvider($applier, $collector)->getFunctions($this->getContainer());

            $this->assertSame($expectedBuilds, $this->builds, $case);
            $this->assertSame($expiresAt, $collector->getExpiration(), $case);
            $this->assertSame(['customer_group'], $collector->getContextDependencies(), $case);
            $this->assertEqualsCanonicalizing([Optimizer::CACHE_TAG, 'ES_VCAT_12'], $collector->stop(), $case);
        }
    }

    /**
     * Test that the cache lifetime is a maximum, the earliest expiration date recorded meanwhile winning.
     *
     * @return void
     */
    public function testExpiration(): void
    {
        $this->getProvider($this->getApplier([], [], null), null, 7200)->getFunctions($this->getContainer());
        $this->assertEqualsWithDelta(7200, $this->storage[self::CACHE_KEY]['lifetime'], 2);

        $this->storage = [];
        $this->getProvider($this->getApplier([], [], time() + 60), null, 7200)->getFunctions($this->getContainer());
        $this->assertEqualsWithDelta(60, $this->storage[self::CACHE_KEY]['lifetime'], 2);
    }

    /**
     * Test that functions built in a non cacheable build are not stored.
     *
     * @return void
     */
    public function testNonCacheableBuild(): void
    {
        $collector = $this->getMockBuilder(QueryDependencyCollector::class)->onlyMethods(['isCacheable'])->getMock();
        $collector->method('isCacheable')->willReturn(false);

        $provider = $this->getProvider($this->getApplier([], [], null), $collector);
        $provider->getFunctions($this->getContainer());
        $provider->getFunctions($this->getContainer());

        $this->assertSame(2, $this->builds);
        $this->assertSame([], $this->storage);
    }

    /**
     * Applier recording some dependencies, as the rule queries it embeds would.
     *
     * @param string[] $cacheTags Cache tags recorded.
     * @param string[] $contexts  Context codes recorded.
     * @param int|null $expiresAt Expiration date recorded.
     *
     * @return ApplierInterface
     */
    private function getApplier(array $cacheTags, array $contexts, ?int $expiresAt): ApplierInterface
    {
        $applier = $this->createMock(ApplierInterface::class);
        $applier->method('getFunction')->willReturnCallback(
            function () use ($cacheTags, $contexts, $expiresAt) {
                $this->builds++;
                $collector = $this->currentCollector;
                $collector->addCacheTags($cacheTags);
                $collector->addContextDependencies($contexts);
                $collector->setExpiration($expiresAt);

                return 'function';
            }
        );

        return $applier;
    }

    /**
     * Provider of the functions of a single optimizer (id 10, model "constant_score"), in a new process sharing the
     * cache.
     *
     * @param ApplierInterface              $applier       Applier.
     * @param QueryDependencyCollector|null $collector     Dependency collector.
     * @param int                           $cacheLifetime Cache lifetime.
     *
     * @return DefaultProvider
     */
    private function getProvider(
        ApplierInterface $applier,
        ?QueryDependencyCollector $collector = null,
        int $cacheLifetime = DefaultProvider::DEFAULT_CACHE_LIFETIME
    ): DefaultProvider {
        $optimizer = $this->createMock(OptimizerInterface::class);
        $optimizer->method('getId')->willReturn(10);
        $optimizer->method('getModel')->willReturn('constant_score');

        $collectionProvider = $this->createMock(CollectionProviderInterface::class);
        $collectionProvider->method('getCollection')->willReturn([$optimizer]);
        $collectionProviderFactory = $this->createMock(CollectionProviderFactory::class);
        $collectionProviderFactory->method('create')->willReturn($collectionProvider);

        $cacheKeyProvider = $this->createMock(CacheKeyProviderInterface::class);
        $cacheKeyProvider->method('getCacheKey')->willReturn(self::CACHE_KEY);

        $this->currentCollector = $collector ?? new QueryDependencyCollector();

        return new DefaultProvider(
            $collectionProviderFactory,
            $this->createMock(CacheInterface::class),
            $cacheKeyProvider,
            ['constant_score' => $applier],
            $cacheLifetime,
            $this->getQueryCache(),
            $this->currentCollector
        );
    }

    /**
     * Rule queries cache on the shared storage of the test, with an empty in-process cache.
     *
     * @return QueryCache
     */
    private function getQueryCache(): QueryCache
    {
        $sharedCache = $this->createMock(ElasticsuiteCache::class);
        $sharedCache->method('load')->willReturnCallback(
            function ($cacheKey) {
                return $this->storage[$cacheKey]['data'] ?? false;
            }
        );
        $sharedCache->method('save')->willReturnCallback(
            function ($data, $cacheKey, $tags, $lifetime) {
                $this->storage[$cacheKey] = ['data' => $data, 'tags' => $tags, 'lifetime' => $lifetime];

                return true;
            }
        );

        $queryContextResolver = $this->createMock(QueryContextResolverInterface::class);
        $queryContextResolver->method('getValues')->willReturnCallback(
            function (array $codes) {
                return array_intersect_key($this->context, array_flip($codes));
            }
        );

        return new QueryCache($sharedCache, $queryContextResolver);
    }

    /**
     * Search container.
     *
     * @return ContainerConfigurationInterface
     */
    private function getContainer(): ContainerConfigurationInterface
    {
        return $this->createMock(ContainerConfigurationInterface::class);
    }
}
