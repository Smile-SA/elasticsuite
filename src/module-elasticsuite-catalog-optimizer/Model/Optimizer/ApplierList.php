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
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory
     */
    private $optimizerCollectionFactory;

    /**
     * @var ApplierInterface[]
     */
    private $appliers;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory                           $queryFactory               Search request query factory.
     * @param \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory $optimizerCollectionFactory Optimizer collection factory.
     * @param ApplierInterface[]                                                                  $appliers                   Appliers interface.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory $optimizerCollectionFactory,
        array $appliers = []
    ) {
        $this->queryFactory               = $queryFactory;
        $this->optimizerCollectionFactory = $optimizerCollectionFactory;
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
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     *
     * @return array
     */
    private function getFunctions(ContainerConfigurationInterface $containerConfiguration)
    {
        $functions = [];

        $optimizers = $this->getOptimizersCollection($containerConfiguration);

        foreach ($optimizers as $optimizer) {
            $function = $this->getFunction($containerConfiguration, $optimizer);

            if ($function !== null) {
                $functions[] = $this->getFunction($containerConfiguration, $optimizer);
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
