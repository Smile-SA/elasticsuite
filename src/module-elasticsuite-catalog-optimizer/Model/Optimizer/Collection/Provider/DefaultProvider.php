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

use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\ProviderInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Optimizer collection provider.
 * Default provider : returns all active optimizers for a given Search Container.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class DefaultProvider implements ProviderInterface
{
    /**
     * @var CollectionFactory
     */
    private $optimizerCollectionFactory;

    /**
     * Provider constructor.
     *
     * @param CollectionFactory $optimizerCollectionFactory Optimizer Collection Factory
     */
    public function __construct(CollectionFactory $optimizerCollectionFactory)
    {
        $this->optimizerCollectionFactory = $optimizerCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE_DEFAULT;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(ContainerConfigurationInterface $containerConfiguration)
    {
        $collection = $this->optimizerCollectionFactory->create();

        $collection->addFieldToFilter(OptimizerInterface::STORE_ID, $containerConfiguration->getStoreId())
            ->addSearchContainersFilter($containerConfiguration->getName())
            ->addIsActiveFilter();

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function useCache()
    {
        return true;
    }
}
