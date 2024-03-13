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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\ProviderInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

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
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var ProviderInterface
     */
    private $functionsProvider;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var OptimizerFilterInterface[]
     */
    private $filters;

    /**
     * Constructor.
     *
     * @param QueryFactory               $queryFactory      Query factory.
     * @param ProviderInterface          $functionsProvider Functions provider.
     * @param ScopeConfigInterface       $scopeConfig       Scope configuration.
     * @param OptimizerFilterInterface[] $filters           Optimizer filters.
     */
    public function __construct(
        QueryFactory $queryFactory,
        ProviderInterface $functionsProvider,
        ScopeConfigInterface $scopeConfig,
        array $filters = []
    ) {
        $this->queryFactory       = $queryFactory;
        $this->functionsProvider  = $functionsProvider;
        $this->scopeConfig        = $scopeConfig;
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
     * @param QueryInterface $query     The Query
     * @param array          $functions The boost functions
     *
     * @return QueryInterface
     */
    private function applyFunctions(QueryInterface $query, $functions = [])
    {
        $scoreModeConfig = $this->scopeConfig->getValue('smile_elasticsuite_optimizers/score_mode_configuration/score_mode');
        $boostModeConfig = $this->scopeConfig->getValue('smile_elasticsuite_optimizers/boost_mode_configuration/boost_mode');

        if (!empty($functions)) {
            $queryParams = [
                'query'     => $query,
                'functions' => $functions,
                'scoreMode' => $scoreModeConfig,
                'boostMode' => $boostModeConfig,
            ];
            $query = $this->queryFactory->create(QueryInterface::TYPE_FUNCTIONSCORE, $queryParams);
        }

        return $query;
    }
}
