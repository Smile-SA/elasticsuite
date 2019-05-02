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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\Provider;

use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\ProviderInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Optimizer Collection provider which returns a collection containing only the optimizer given in construct.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Only implements ProviderInterface
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
     * @param OptimizerInterface $optimizer                  The optimizer we ONLY want to apply.
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
        return self::TYPE_ONLY;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(ContainerConfigurationInterface $containerConfiguration)
    {
        $collection = $this->optimizerCollectionFactory->create();

        // Force an empty collection loading.
        $collection->addFieldToFilter('main_table.' . OptimizerInterface::OPTIMIZER_ID, ['eq' => 0]);

        // Add the current optimizer as the only item.
        $collection->addItem($this->optimizer);

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
