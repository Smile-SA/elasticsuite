<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2022 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\Provider;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ObjectManager;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\ProviderFactory as CollectionProviderFactory;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\ProviderInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\CacheKeyProviderInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Collection;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryDependencyCollector;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Optimizer functions provider.
 * Default provider : returns the fuctions of all active optimizers for a given Search Container.
 *
 * The functions are cached along with the dependencies of the rule queries they embed: they are stored per value of
 * the contexts these queries depend on (eg. the customer group, for a "is discount" condition), expire with them, and
 * are tagged with their cache tags (eg. the virtual categories they embed).
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class DefaultProvider implements ProviderInterface
{
    /**
     * @var int default lifetime for computed optimizers functions.
     */
    const DEFAULT_CACHE_LIFETIME = 7200;

    /**
     * @var CollectionProviderFactory
     */
    private $providerFactory;

    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\CacheKeyProviderInterface
     */
    private $cacheKeyProvider;

    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\ApplierInterface[]
     */
    private $appliers;

    /**
     * @var integer|mixed
     */
    private $cacheLifeTime;

    /**
     * @var QueryCache
     */
    private $queryCache;

    /**
     * @var QueryDependencyCollector
     */
    private $dependencyCollector;

    /**
     * Provider constructor.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param CollectionProviderFactory     $collectionProviderFactory Optimizer Collection Provider Factory
     * @param CacheInterface                $cache                     Deprecated, unused: the functions are cached by
     *                                                                 the rule queries cache.
     * @param CacheKeyProviderInterface     $cacheKeyProvider          Cache Key Provider
     * @param array                         $appliers                  Optimizers appliers
     * @param int                           $cacheLifetime             Cache Lifetime of computed Optimizers functions
     * @param QueryCache|null               $queryCache                Rule queries cache.
     * @param QueryDependencyCollector|null $dependencyCollector       Rule queries dependency collector.
     */
    public function __construct(
        CollectionProviderFactory $collectionProviderFactory,
        CacheInterface $cache,
        CacheKeyProviderInterface $cacheKeyProvider,
        array $appliers = [],
        $cacheLifetime = self::DEFAULT_CACHE_LIFETIME,
        ?QueryCache $queryCache = null,
        ?QueryDependencyCollector $dependencyCollector = null
    ) {
        $this->providerFactory     = $collectionProviderFactory;
        $this->cacheKeyProvider    = $cacheKeyProvider;
        $this->appliers            = $appliers;
        $this->cacheLifeTime       = $cacheLifetime;
        $this->queryCache          = $queryCache ?: ObjectManager::getInstance()->get(QueryCache::class);
        $this->dependencyCollector = $dependencyCollector ?: ObjectManager::getInstance()->get(
            QueryDependencyCollector::class
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE_DEFAULT;
    }

    /**
     * Returns functions applied to the container.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     *
     * @return array
     */
    public function getFunctions(ContainerConfigurationInterface $containerConfiguration)
    {
        // The contexts (eg. the customer group) are only added to the key of the functions depending on them, by the
        // query cache.
        $cacheKey  = $this->cacheKeyProvider->getCacheKey($containerConfiguration);
        $cacheData = $this->queryCache->load($cacheKey);

        if ($cacheData !== false) {
            // Data embedding these functions depends on what they depend on.
            $this->dependencyCollector->addCacheTags($cacheData['tags']);
            $this->dependencyCollector->setExpiration($cacheData['expiresAt']);
            $this->dependencyCollector->addContextDependencies($cacheData['context']);

            return $cacheData['data'];
        }

        // Every dependency of the rule queries built meanwhile (eg. the virtual categories they embed) is recorded.
        $this->dependencyCollector->start();
        try {
            $this->dependencyCollector->addCacheTags([Optimizer::CACHE_TAG]);
            if ($this->cacheLifeTime) {
                // The optimizers applying to the container (eg. according to their dates) can change meanwhile.
                $this->dependencyCollector->setExpiration(time() + (int) $this->cacheLifeTime);
            }
            $optimizers = $this->getCollection($containerConfiguration);
            $functions  = $this->getOptimizersFunctions($containerConfiguration, $optimizers);
        } finally {
            $isCacheable         = $this->dependencyCollector->isCacheable();
            $expiresAt           = $this->dependencyCollector->getExpiration();
            $contextDependencies = $this->dependencyCollector->getContextDependencies();
            $cacheTags           = $this->dependencyCollector->stop();
        }

        if ($isCacheable) {
            $this->queryCache->save($cacheKey, $functions, $cacheTags, $expiresAt, $contextDependencies);
        }

        return $functions;
    }

    /**
     * Build function score for a container / optimizer.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     * @param OptimizerInterface              $optimizer              Optimizer.
     *
     * @return  mixed
     */
    protected function getFunction(ContainerConfigurationInterface $containerConfiguration, OptimizerInterface $optimizer)
    {
        $function = null;
        $type     = $optimizer->getModel();

        if (isset($this->appliers[$type])) {
            $function = $this->appliers[$type]->getFunction($containerConfiguration, $optimizer);
        }

        return $function;
    }

    /**
     * Retrieve Optimizers collection for a given Container
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container Configuration
     *
     * @return \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Collection
     */
    private function getCollection(ContainerConfigurationInterface $containerConfiguration)
    {
        $provider = $this->providerFactory->create(self::TYPE_DEFAULT);

        return $provider->getCollection($containerConfiguration);
    }

    /**
     * Retrieve optimizers functions for a given container
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container Configuration
     * @param Collection|OptimizerInterface[] $optimizers             Optimizers list
     *
     * @return array
     */
    private function getOptimizersFunctions(ContainerConfigurationInterface $containerConfiguration, $optimizers)
    {
        $functions = [];

        foreach ($optimizers as $optimizer) {
            $function = $this->getFunction($containerConfiguration, $optimizer);

            if ($function !== null) {
                $functions[$optimizer->getId()] = $function;
            }
        }

        return $functions;
    }
}
