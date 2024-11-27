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
     * @var \Magento\Catalog\Api\CategoryRepositoryInterfaceFactory
     */
    private $categoryRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\App\Action\Context                   $context            Controller action context.
     * @param \Magento\Framework\Controller\Result\JsonFactory        $jsonResultFactory  JSON result factory.
     * @param \Magento\Catalog\Model\Layer\Resolver                   $layerResolver      Layer resolver.
     * @param \Magento\Catalog\Api\CategoryRepositoryInterfaceFactory $categoryRepository Category factory.
     * @param \Psr\Log\LoggerInterface                                $logger             Logger.
     * @param \Magento\Catalog\Model\Layer\FilterList[]               $filterListPool     Filter list pool.
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Api\CategoryRepositoryInterfaceFactory $categoryRepository,
        \Psr\Log\LoggerInterface $logger,
        $filterListPool = []
    ) {
        parent::__construct($context);

        $this->jsonResultFactory = $jsonResultFactory;
        $this->layerResolver     = $layerResolver;
        $this->filterListPool    = $filterListPool;
        $this->categoryRepository = $categoryRepository;
        $this->logger             = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $this->initLayer();

        $items  = $this->getItems();
        $result = $this->jsonResultFactory->create()->setData($items);

        return $result;
    }

    /**
     * Current navigation layer type (search or category).
     *
     * @return string
     */
    private function getLayerType()
    {
        $layerType = Resolver::CATALOG_LAYER_CATEGORY;

        if ($this->isSearch()) {
            $layerType  = Resolver::CATALOG_LAYER_SEARCH;
        }

        return $layerType;
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
            try {
                $category = $this->categoryRepository->create()->get(
                    $this->getRequest()->getParam('cat'),
                    $this->layerResolver->get()->getCurrentStore()->getId()
                );

                $this->layerResolver->get()->setCurrentCategory($category);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                $this->logger->critical($exception->getMessage());
            }
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

        foreach ($filterList->getFilters($layer) as $filter) {
            if ($filter->getRequestVar() == $this->getFilterName()) {
                foreach ($filter->getItems() as $item) {
                    $item->setBaseUrl($this->_redirect->getRedirectUrl());
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
        return (bool) ($this->getRequest()->getParam('q') !== null);
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
