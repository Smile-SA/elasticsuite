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
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Category;

use Magento\Catalog\Api\CategoryRepositoryInterfaceFactory;
use Magento\Framework\App\RequestInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\OptimizerFilterInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Limitation;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;

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
     * @var CategoryRepositoryInterfaceFactory
     */
    private $categoryRepository;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * Constructor.
     *
     * @param ContextInterface                        $searchContext             Search context.
     * @param Limitation                              $limitationResource        Optimizer Limitation.
     * @param CategoryRepositoryInterfaceFactory      $categoryRepositoryFactory Category Repository Factory.
     * @param \Magento\Framework\App\RequestInterface $request                   Request.
     */
    public function __construct(
        ContextInterface $searchContext,
        Limitation $limitationResource,
        CategoryRepositoryInterfaceFactory $categoryRepositoryFactory,
        RequestInterface $request
    ) {
        $this->limitationResource = $limitationResource;
        $this->searchContext      = $searchContext;
        $this->categoryRepository = $categoryRepositoryFactory->create();
        $this->request            = $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptimizerIds()
    {
        $optimizerIds = null;

        if (!$this->searchContext->getCurrentCategory() && $this->getPreviewCategory()) {
            $category = $this->categoryRepository->get($this->getPreviewCategory());
            $this->searchContext->setCurrentCategory($category);
        }

        if ($this->searchContext->getCurrentCategory()) {
            $storeId    = $this->searchContext->getStoreId();
            $categoryId = (int) $this->searchContext->getCurrentCategory()->getId();
            $cacheKey   = sprintf("%s_%s", $categoryId, $storeId);

            if (!isset($this->cache[$cacheKey])) {
                $this->cache[$cacheKey] = $this->limitationResource->getApplicableOptimizerIdsByCategoryId($categoryId);
            }

            $optimizerIds = $this->cache[$cacheKey];
        }
        $optimizerIds[] = $this->getCurrentOptimizerId();

        return $optimizerIds;
    }

    /**
     * @return mixed
     */
    private function getPreviewCategory()
    {
        return $this->request->getPostValue('category_preview') ?? false;
    }

    /**
     * @return mixed
     */
    private function getCurrentOptimizerId()
    {
        return $this->request->getPostValue('optimizer_id') ?? '';
    }
}
