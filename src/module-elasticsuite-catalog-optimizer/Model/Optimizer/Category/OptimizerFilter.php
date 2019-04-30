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
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Category;

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
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Api\Search\ContextInterface                          $searchContext      Search context.
     * @param \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Limitation $limitationResource Optimizer Limitation.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Api\Search\ContextInterface $searchContext,
        \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Limitation $limitationResource
    ) {
        $this->limitationResource = $limitationResource;
        $this->searchContext      = $searchContext;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptimizerIds()
    {
        $optimizerIds = null;

        if ($this->searchContext->getCurrentCategory()) {
            $storeId    = $this->searchContext->getStoreId();
            $categoryId = (int) $this->searchContext->getCurrentCategory()->getId();
            $cacheKey   = sprintf("%s_%s", $categoryId, $storeId);

            if (!isset($this->cache[$cacheKey])) {
                $this->cache[$cacheKey] = $this->limitationResource->getApplicableOptimizerIdsByCategoryId($categoryId);
            }

            $optimizerIds = $this->cache[$cacheKey];
        }

        return $optimizerIds;
    }
}
