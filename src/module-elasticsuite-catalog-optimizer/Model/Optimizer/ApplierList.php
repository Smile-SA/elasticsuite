<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;

use Magento\Framework\App\CacheInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\ProviderInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Collection;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\FunctionScore;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;

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
     * @var string
     */
    const CACHE_KEY_PREFIX = 'optimizer_boost_function';

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\ProviderInterface
     */
    private $collectionProvider;

    /**
     * @var ApplierInterface[]
     */
    private $appliers;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $functions = [];

    /**
     * Constructor.
     *
     * @param QueryFactory       $queryFactory       Search request query factory.
     * @param ProviderInterface  $collectionProvider Optimizer Collection Provider
     * @param CacheInterface     $cache              Application cache.
     * @param ApplierInterface[] $appliers           Appliers interface.
     */
    public function __construct(
        QueryFactory $queryFactory,
        ProviderInterface $collectionProvider,
        CacheInterface $cache,
        array $appliers = []
    ) {
        $this->queryFactory       = $queryFactory;
        $this->collectionProvider = $collectionProvider;
        $this->cache              = $cache;
        $this->appliers           = $appliers;
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
        $containerName = $containerConfiguration->getName();
        $storeId       = $containerConfiguration->getStoreId();

        $cacheKey = sprintf("%s_%s_%s", self::CACHE_KEY_PREFIX, $containerName, $storeId);

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
                $functions[] = $function;
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
