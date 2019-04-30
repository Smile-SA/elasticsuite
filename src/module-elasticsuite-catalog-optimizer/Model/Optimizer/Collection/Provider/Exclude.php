<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticSuite________
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\Provider;

use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\ProviderInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Optimizer collection provider : Returns all current optimizers for a given container, except one.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Exclude implements ProviderInterface
{
    /**
     * @var CollectionFactory
     */
    private $optimizerCollectionFactory;

    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface
     */
    private $optimizer;

    /**
     * Provider constructor.
     *
     * @param CollectionFactory  $optimizerCollectionFactory Optimizer Collection Factory.
     * @param OptimizerInterface $optimizer                  The optimizer we do NOT want to apply.
     */
    public function __construct(CollectionFactory $optimizerCollectionFactory, OptimizerInterface $optimizer)
    {
        $this->optimizerCollectionFactory = $optimizerCollectionFactory;
        $this->optimizer                  = $optimizer;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE_EXCLUDE;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(ContainerConfigurationInterface $containerConfiguration)
    {
        $collection = $this->optimizerCollectionFactory->create();

        $collection->addFieldToFilter(OptimizerInterface::STORE_ID, $containerConfiguration->getStoreId())
            ->addSearchContainersFilter($containerConfiguration->getName())
            ->addIsActiveFilter()
            ->addFieldToFilter('main_table.' . OptimizerInterface::OPTIMIZER_ID, ['neq' => $this->optimizer->getId()]);

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function useCache()
    {
        return false;
    }
}
