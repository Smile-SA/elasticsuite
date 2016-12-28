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

use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
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
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory
     */
    private $optimizerCollectionFactory;

    /**
     * @var ApplierInterface[]
     */
    private $appliers;


    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $functions = [];

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory                           $queryFactory               Search request query factory.
     * @param \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory $optimizerCollectionFactory Optimizer collection factory.
     * @param \Magento\Framework\App\CacheInterface                                               $cache                      Application cache.
     * @param ApplierInterface[]                                                                  $appliers                   Appliers interface.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory $optimizerCollectionFactory,
        \Magento\Framework\App\CacheInterface $cache,
        array $appliers = []
    ) {
        $this->queryFactory               = $queryFactory;
        $this->optimizerCollectionFactory = $optimizerCollectionFactory;
        $this->cache                      = $cache;
        $this->appliers                   = $appliers;
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
     *
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
            if ($functions = $this->cache->load($cacheKey)) {
                $functions = unserialize($functions);
            } else {
                $functions = [];
                $optimizers = $this->getOptimizersCollection($containerConfiguration);

                foreach ($optimizers as $optimizer) {
                    $function = $this->getFunction($containerConfiguration, $optimizer);

                    if ($function !== null) {
                        $functions[] = $function;
                    }
                }

                $this->cache->save(serialize($functions), $cacheKey, [Optimizer::CACHE_TAG], 7200);
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
     * @return OptimizerCollectionFactory
     */
    private function getOptimizersCollection(ContainerConfigurationInterface $containerConfiguration)
    {
        $collection = $this->optimizerCollectionFactory->create();

        $collection->addFieldToFilter(OptimizerInterface::STORE_ID, $containerConfiguration->getStoreId())
            ->addSearchContainersFilter($containerConfiguration->getName())
            ->addIsActiveFilter();

        return $collection;
    }
}
