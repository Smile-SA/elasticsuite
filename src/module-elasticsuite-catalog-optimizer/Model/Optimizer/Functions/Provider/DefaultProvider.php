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
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\ProviderFactory as CollectionProviderFactory;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\ProviderInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\CacheKeyProviderInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Collection;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Optimizer functions provider.
 * Default provider : returns the fuctions of all active optimizers for a given Search Container.
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
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

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
     * @var array
     */
    private $functions;

    /**
     * Provider constructor.
     *
     * @param CollectionProviderFactory $collectionProviderFactory Optimizer Collection Provider Factory
     * @param CacheInterface            $cache                     Cache Interface
     * @param CacheKeyProviderInterface $cacheKeyProvider          Cache Key Provider
     * @param array                     $appliers                  Optimizers appliers
     * @param int                       $cacheLifetime             Cache Lifetime of computed Optimizers functions
     */
    public function __construct(
        CollectionProviderFactory $collectionProviderFactory,
        CacheInterface $cache,
        CacheKeyProviderInterface $cacheKeyProvider,
        array $appliers = [],
        $cacheLifetime = self::DEFAULT_CACHE_LIFETIME
    ) {
        $this->providerFactory  = $collectionProviderFactory;
        $this->cache            = $cache;
        $this->cacheKeyProvider = $cacheKeyProvider;
        $this->appliers         = $appliers;
        $this->cacheLifeTime    = $cacheLifetime;
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
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     *
     * @return array
     */
    public function getFunctions(ContainerConfigurationInterface $containerConfiguration)
    {
        $cacheKey = $this->cacheKeyProvider->getCacheKey($containerConfiguration);

        if (!isset($this->functions[$cacheKey])) {
            if ($functions = $this->cache->load($cacheKey)) {
                $functions = unserialize($functions);
            } else {
                $optimizers = $this->getCollection($containerConfiguration);
                $functions  = $this->getOptimizersFunctions($containerConfiguration, $optimizers);
                $this->cache->save(serialize($functions), $cacheKey, [Optimizer::CACHE_TAG], $this->cacheLifeTime);
            }

            $this->functions[$cacheKey] = $functions;
        }

        return $this->functions[$cacheKey];
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
