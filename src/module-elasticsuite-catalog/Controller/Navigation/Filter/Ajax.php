<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Controller\Navigation\Filter;

use Magento\Catalog\Model\Layer\Resolver;

/**
 * Navigation layer filters AJAX loading.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Ajax extends \Magento\Framework\App\Action\Action
{
    /**
     * Catalog Layer Resolver
     *
     * @var \Magento\Catalog\Model\Layer\Resolver
     */
    private $layerResolver;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $jsonResultFactory;

    /**
     *
     * @var \Magento\Catalog\Model\Layer\FilterList[]
     */
    private $filterListPool;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\App\Action\Context            $context            Controller action context.
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory  JSON result factory.
     * @param \Magento\Catalog\Model\Layer\Resolver            $layerResolver      Layer resolver.
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository Category Repository.
     * @param \Magento\Catalog\Model\Layer\FilterList[]        $filterListPool     Filter list pool.
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        $filterListPool = []
    ) {
        parent::__construct($context);

        $this->jsonResultFactory = $jsonResultFactory;
        $this->layerResolver     = $layerResolver;
        $this->filterListPool    = $filterListPool;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $this->initLayer();

        return $this->jsonResultFactory->create()->setData($this->getItems());
    }

    /**
     * Current navigation layer type (search or category).
     *
     * @return string
     */
    private function getLayerType()
    {
        return $this->isSearch() ? Resolver::CATALOG_LAYER_SEARCH : Resolver::CATALOG_LAYER_CATEGORY;
    }

    /**
     * Init the current navigation layer.
     *
     * @return \Smile\ElasticsuiteCatalog\Controller\Navigation\Filter\Ajax
     */
    private function initLayer()
    {
        $this->layerResolver->create($this->getLayerType());

        if ($this->getRequest()->getParam('cat')) {
            $category = $this->categoryRepository->get(
                $this->getRequest()->getParam('cat'),
                $this->layerResolver->get()->getCurrentStore()->getId()
            );

            $this->layerResolver->get()->setCurrentCategory($category);
        }

        $this->applyFilters();

        $this->layerResolver->get()->getProductCollection()->setPageSize(0);

        return $this;
    }

    /**
     * Return the current filter list for the request.
     *
     * @return \Magento\Catalog\Model\Layer\FilterList
     */
    private function getFilterList()
    {
        return $this->filterListPool[$this->getLayerType()];
    }

    /**
     * Apply current filters to the layer product collection.
     *
     * @return \Smile\ElasticsuiteCatalog\Controller\Navigation\Filter\Ajax
     */
    private function applyFilters()
    {
        $layer      = $this->layerResolver->get();
        $filterList = $this->getFilterList();

        foreach ($filterList->getFilters($layer) as $filter) {
            $filter->apply($this->getRequest());
        }

        $layer->apply();

        return $this;
    }

    /**
     * Returns array of items loaded through ajax.
     *
     * @return array
     */
    private function getItems()
    {
        $items = [];

        $layer = $this->layerResolver->get();
        $filterList = $this->getFilterList();
        $filterName = (string) $this->getFilterName();
        $baseUrl = $this->_redirect->getRedirectUrl();

        foreach ($filterList->getFilters($layer) as $filter) {
            if ($filter->getRequestVar() === $filterName) {
                foreach ($filter->getItems() as $item) {
                    $item->setBaseUrl($baseUrl);
                    $items[] = $item->toArray(['url', 'count', 'is_selected', 'label']);
                }
            }
        }

        return $items;
    }

    /**
     * Is the current request a search.
     *
     * @return boolean
     */
    private function isSearch()
    {
        return $this->getRequest()->getParam('q') !== null;
    }

    /**
     * Current request filter name.
     *
     * @return string
     */
    private function getFilterName()
    {
        return (string) $this->getRequest()->getParam('filterName');
    }
}
