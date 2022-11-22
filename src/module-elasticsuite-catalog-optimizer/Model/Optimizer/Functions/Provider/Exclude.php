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
 * @copyright 2022 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\Provider;

use Magento\Framework\App\CacheInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\ProviderFactory as CollectionProviderFactory;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\ProviderInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\CacheKeyProviderInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Optimizer functions provider : Returns all functions of optimizers for a given container, except one.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Exclude extends DefaultProvider implements ProviderInterface
{
    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface
     */
    private $optimizer;

    /**
     * Provider constructor.
     *
     * @param CollectionProviderFactory $collectionProviderFactory Optimizer Collection Provider Factory
     * @param CacheInterface            $cache                     Cache Interface
     * @param CacheKeyProviderInterface $cacheKeyProvider          Cache Key Provider
     * @param OptimizerInterface        $optimizer                 Optimizer being excluded
     * @param array                     $appliers                  Optimizers appliers
     */
    public function __construct(
        CollectionProviderFactory $collectionProviderFactory,
        CacheInterface $cache,
        CacheKeyProviderInterface $cacheKeyProvider,
        OptimizerInterface $optimizer,
        array $appliers = []
    ) {
        parent::__construct($collectionProviderFactory, $cache, $cacheKeyProvider, $appliers);
        $this->optimizer = $optimizer;
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
    public function getFunctions(ContainerConfigurationInterface $containerConfiguration)
    {
        $functions = parent::getFunctions($containerConfiguration);

        if (isset($functions[$this->optimizer->getId()])) {
            unset($functions[$this->optimizer->getId()]);
        }

        return $functions;
    }
}
