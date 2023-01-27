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
 * @copyright 2020 Smile
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
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\ProviderInterface
     */
    private $functionsProvider;

    /**
     * @var OptimizerFilterInterface[]
     */
    private $filters;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory                       $queryFactory      Query factory.
     * @param \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\ProviderInterface $functionsProvider Functions provider.
     * @param OptimizerFilterInterface[]                                                      $filters           Optimizer filters.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\ProviderInterface $functionsProvider,
        array $filters = []
    ) {
        $this->queryFactory       = $queryFactory;
        $this->functionsProvider  = $functionsProvider;
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
        $functions = $this->functionsProvider->getFunctions($containerConfiguration);

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
}
