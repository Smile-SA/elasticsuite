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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Search;

use Magento\Framework\App\RequestInterface;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\OptimizerFilterInterface;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Limitation;

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
     * @var Limitation
     */
    private $limitationResource;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var ContextInterface
     */
    private $searchContext;

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var string
     */
    private $containerName;

    /**
     * Constructor.
     *
     * @param ContextInterface $searchContext      Search context.
     * @param Limitation       $limitationResource Optimizer Limitation.
     * @param QueryFactory     $queryFactory       Query Factory.
     * @param RequestInterface $request            Request.
     * @param string           $containerName      Container Name.
     */
    public function __construct(
        ContextInterface $searchContext,
        Limitation $limitationResource,
        QueryFactory $queryFactory,
        RequestInterface $request,
        $containerName = 'quick_search_container'
    ) {
        $this->limitationResource = $limitationResource;
        $this->searchContext      = $searchContext;
        $this->containerName      = $containerName;
        $this->queryFactory       = $queryFactory;
        $this->request            = $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptimizerIds()
    {
        $optimizerIds = null;

        if (!$this->searchContext->getCurrentSearchQuery() && $this->getPreviewSearchQuery()) {
            $query = $this->queryFactory->create();
            $query->loadByQueryText($this->getPreviewSearchQuery());
            $this->searchContext->setCurrentSearchQuery($query);
        }

        if ($this->searchContext->getCurrentSearchQuery()) {
            $storeId  = $this->searchContext->getStoreId();
            $queryId  = (int) $this->searchContext->getCurrentSearchQuery()->getId();
            $cacheKey = sprintf("%s_%s", $queryId, $storeId);

            if (!isset($this->cache[$cacheKey])) {
                $this->cache[$cacheKey] = $this->limitationResource->getApplicableOptimizerIdsByQueryId($queryId, $this->containerName);
            }

            $optimizerIds = $this->cache[$cacheKey];
        }
        $optimizerIds[] = $this->getCurrentOptimizerId();

        return $optimizerIds;
    }

    /**
     * @return mixed
     */
    private function getPreviewSearchQuery()
    {
        return $this->request->getPostValue('query_text_preview') ?? false;
    }

    /**
     * @return mixed
     */
    private function getCurrentOptimizerId()
    {
        return $this->request->getPostValue('optimizer_id') ?? '';
    }
}
