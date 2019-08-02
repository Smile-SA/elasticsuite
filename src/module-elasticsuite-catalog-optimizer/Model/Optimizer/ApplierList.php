<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;

use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Collection;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\FunctionScore;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\CacheKeyProviderInterface;

/**
 * Apply the list of optimizations to a query for a given container.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class ApplierList
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\ProviderInterface
     */
    private $collectionProvider;

    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\ApplierInterface[]
     */
    private $appliers;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\CacheKeyProviderInterface
     */
    private $cacheKeyProvider;

    /**
     * @var array
     */
    private $functions = [];

    /**
     * @var OptimizerFilterInterface[]
     */
    private $filters;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory                        $queryFactory       Query factory.
     * @param \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\ProviderInterface $collectionProvider Collection Provider.
     * @param \Magento\Framework\App\CacheInterface                                            $cache              Application cache.
     * @param CacheKeyProviderInterface                                                        $cacheKeyProvider   Cache key provider.
     * @param \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\ApplierInterface[]           $appliers           Appliers interface.
     * @param OptimizerFilterInterface[]                                                       $filters            Optimizer filters.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\ProviderInterface $collectionProvider,
        \Magento\Framework\App\CacheInterface $cache,
        \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\CacheKeyProviderInterface $cacheKeyProvider,
        array $appliers = [],
        array $filters = []
    ) {
        $this->queryFactory       = $queryFactory;
        $this->collectionProvider = $collectionProvider;
        $this->cache              = $cache;
        $this->cacheKeyProvider   = $cacheKeyProvider;
        $this->appliers           = $appliers;
        $this->filters            = $filters;
    }

    /**
     * Returns query.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     * @param QueryInterface                  $query                  Query.
     *
     * @return QueryInterface
     */
    public function applyOptimizers(ContainerConfigurationInterface $containerConfiguration, QueryInterface $query)
    {
        $functions = $this->getFunctions($containerConfiguration);

        if (isset($this->filters[$containerConfiguration->getName()])) {
            $optimizerIds = $this->filters[$containerConfiguration->getName()]->getOptimizerIds() ?? array_keys($functions);
            $functions = array_intersect_key($functions, array_flip($optimizerIds));
        }

        return $this->applyFunctions($query, $functions);
    }

    /**
     * Apply boost functions to a given query.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\QueryInterface $query     The Query
     * @param array                                                 $functions The boost functions
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    private function applyFunctions(QueryInterface $query, $functions = [])
    {
        if (!empty($functions)) {
            $queryParams = [
                'query'     => $query,
                'functions' => $functions,
                'scoreMode' => FunctionScore::SCORE_MODE_MULTIPLY,
                'boostMode' => FunctionScore::BOOST_MODE_MULTIPLY,
            ];
            $query = $this->queryFactory->create(QueryInterface::TYPE_FUNCTIONSCORE, $queryParams);
        }

        return $query;
    }

    /**
     * Returns functions applied to the container.
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     *
     * @return array
     */
    private function getFunctions(ContainerConfigurationInterface $containerConfiguration)
    {
        $cacheKey = $this->cacheKeyProvider->getCacheKey($containerConfiguration);

        if (!isset($this->functions[$cacheKey])) {
            if ($this->collectionProvider->useCache() && ($functions = $this->cache->load($cacheKey))) {
                $functions = unserialize($functions);
            } else {
                $optimizers = $this->getOptimizersCollection($containerConfiguration);
                $functions  = $this->getOptimizersFunctions($containerConfiguration, $optimizers);

                if ($this->collectionProvider->useCache()) {
                    $this->cache->save(serialize($functions), $cacheKey, [Optimizer::CACHE_TAG], 7200);
                }
            }

            $this->functions[$cacheKey] = $functions;
        }

        return $this->functions[$cacheKey];
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

    /**
     * Build function score for a container / optimizer.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     * @param OptimizerInterface              $optimizer              Optimizer.
     *
     * @return  mixed
     */
    private function getFunction(ContainerConfigurationInterface $containerConfiguration, OptimizerInterface $optimizer)
    {
        $function = null;
        $type     = $optimizer->getModel();

        if (isset($this->appliers[$type])) {
            $function = $this->appliers[$type]->getFunction($containerConfiguration, $optimizer);
        }

        return $function;
    }

    /**
     * Get optimizers applied by container.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     *
     * @return Collection
     */
    private function getOptimizersCollection(ContainerConfigurationInterface $containerConfiguration)
    {
        return $this->collectionProvider->getCollection($containerConfiguration);
    }
}
