<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;

/**
 * Return a list of optimizers for a given search context.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class OptimizerList
{
    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Limitation
     */
    private $limitationResource;

    /**
     * @var array
     */
    private $optimizersByCategory = [];

    /**
     * @var array
     */
    private $optimizersBySearchTerm = [];

    /**
     * OptimizerList constructor.
     *
     * @param \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Limitation $limitationResource Optimizer Limitation.
     */
    public function __construct(
        \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Limitation $limitationResource
    ) {
        $this->limitationResource = $limitationResource;
    }

    /**
     * Retrieve only applicable optimizers for a given search context.
     *
     * @param \Smile\ElasticsuiteCore\Api\Search\ContextInterface $context        Search context
     * @param array                                               $optimizersList Array of optimizers
     *
     * @return array
     */
    public function getOptimizers(
        \Smile\ElasticsuiteCore\Api\Search\ContextInterface $context,
        array $optimizersList
    ) {
        $optimizerIds = array_keys($optimizersList);

        if ($context->getCurrentCategory()) {
            $optimizerIds = $this->getByCategoryId($context);
        } elseif ($context->getCurrentSearchQuery() && $context->getCurrentSearchQuery()->getId()) {
            $optimizerIds = $this->getByQueryId($context);
        }

        return array_intersect_key($optimizersList, array_flip($optimizerIds));
    }

    /**
     * Get Relevant Optimizers by category Id.
     *
     * @param \Smile\ElasticsuiteCore\Api\Search\ContextInterface $context Search Context
     *
     * @return array
     */
    private function getByCategoryId(\Smile\ElasticsuiteCore\Api\Search\ContextInterface $context)
    {
        $storeId    = $context->getStoreId();
        $categoryId = (int) $context->getCurrentCategory()->getId();
        $cacheKey   = sprintf("%s_%s", $categoryId, $storeId);

        if (!isset($this->optimizersByCategory[$cacheKey])) {
            $this->optimizersByCategory[$cacheKey] = $this->limitationResource->getApplicableOptimizerIdsByCategoryId($categoryId);
        }

        return $this->optimizersByCategory[$cacheKey];
    }

    /**
     * Get Relevant Optimizers by query Id.
     *
     * @param \Smile\ElasticsuiteCore\Api\Search\ContextInterface $context Search Context
     *
     * @return array
     */
    private function getByQueryId(\Smile\ElasticsuiteCore\Api\Search\ContextInterface $context)
    {
        $storeId  = $context->getStoreId();
        $queryId  = (int) $context->getCurrentSearchQuery()->getId();
        $cacheKey = sprintf("%s_%s", $queryId, $storeId);

        if (!isset($this->optimizersBySearchTerm[$cacheKey])) {
            $this->optimizersBySearchTerm[$cacheKey] = $this->limitationResource->getApplicableOptimizerIdsByQueryId($queryId);
        }

        return $this->optimizersBySearchTerm[$cacheKey];
    }
}
