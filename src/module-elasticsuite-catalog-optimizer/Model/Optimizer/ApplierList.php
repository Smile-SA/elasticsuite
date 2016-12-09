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

use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory as OptimizerCollectionFactory;
use Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\FunctionScore;

/**
 * ApplierList Model
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
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
     * @var OptimizerCollectionFactory
     */
    private $optimizerCollectionFactory;

    /**
     * @var ApplierInterface[]
     */
    private $appliers;

    /**
     * Constructor.
     *
     * @param QueryFactory               $queryFactory               Search request query factory.
     * @param OptimizerCollectionFactory $optimizerCollectionFactory Optimizer collection factory.
     * @param ApplierInterface[]         $appliers                   Appliers interface.
     *
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        QueryFactory $queryFactory,
        OptimizerCollectionFactory $optimizerCollectionFactory,
        array $appliers = []
    ) {
        $this->queryFactory               = $queryFactory;
        $this->optimizerCollectionFactory = $optimizerCollectionFactory;
        $this->appliers                   = $appliers;
    }

    /**
     * Returns query.
     *
     * @param ContainerConfiguration $containerConfiguration Container configuration.
     * @param QueryInterface         $query                  Query.
     *
     * @SuppressWarnings(PHPMD.LongVariable)
     * @return QueryInterface
     */
    public function applyOptimizers(ContainerConfiguration $containerConfiguration, QueryInterface $query)
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
     * Returns functions.
     *
     * @param ContainerConfiguration $containerConfiguration Container configuration.
     *
     * @SuppressWarnings(PHPMD.LongVariable)
     * @return array
     */
    private function getFunctions($containerConfiguration)
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
     * @param ContainerConfiguration $containerConfiguration Container configuration.
     * @param Optimizer              $optimizer              Optimizer.
     *
     * @SuppressWarnings(PHPMD.LongVariable)
     * @return  mixed
     */
    private function getFunction($containerConfiguration, $optimizer)
    {
        $function = null;
        $type     = $optimizer->getModel();

        if (isset($this->appliers[$type])) {
            $function = $this->appliers[$type]->getFunction($containerConfiguration, $optimizer);
        }

        return $function;
    }

    /**
     * @param ContainerConfiguration $containerConfiguration Container configuration.
     *
     * @SuppressWarnings(PHPMD.LongVariable)
     * @return OptimizerCollectionFactory
     */
    private function getOptimizersCollection($containerConfiguration)
    {
        $collection = $this->optimizerCollectionFactory->create();

        return $collection
            ->addFieldToFilter(OptimizerInterface::STORE_ID, $containerConfiguration->getStoreId())
            ->addSearchContainersFilter($containerConfiguration->getName())
            ->addIsActiveFilter();
    }
}
