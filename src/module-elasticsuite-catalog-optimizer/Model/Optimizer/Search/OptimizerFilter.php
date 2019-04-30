<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Search;

use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\OptimizerFilterInterface;

/**
 * Return a list of optimizers for a given search context.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class OptimizerFilter implements OptimizerFilterInterface
{
    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Limitation
     */
    private $limitationResource;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var \Smile\ElasticsuiteCore\Api\Search\ContextInterface
     */
    private $searchContext;

    /**
     * @var string
     */
    private $containerName;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Api\Search\ContextInterface                          $searchContext      Search context.
     * @param \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Limitation $limitationResource Optimizer Limitation.
     * @param string                                                                       $containerName      Container Name.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Api\Search\ContextInterface $searchContext,
        \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Limitation $limitationResource,
        $containerName = 'quick_search_container'
    ) {
        $this->limitationResource = $limitationResource;
        $this->searchContext      = $searchContext;
        $this->containerName      = $containerName;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptimizerIds()
    {
        $optimizerIds = null;

        if ($this->searchContext->getCurrentSearchQuery()) {
            $storeId  = $this->searchContext->getStoreId();
            $queryId  = (int) $this->searchContext->getCurrentSearchQuery()->getId();
            $cacheKey = sprintf("%s_%s", $queryId, $storeId);

            if (!isset($this->cache[$cacheKey])) {
                $this->cache[$cacheKey] = $this->limitationResource->getApplicableOptimizerIdsByQueryId($queryId, $this->containerName);
            }

            $optimizerIds = $this->cache[$cacheKey];
        }

        return $optimizerIds;
    }
}
