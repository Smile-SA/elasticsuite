<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Search\Request\Product\Attribute\Aggregation;

use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\AggregationInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Magento\Catalog\Model\CategoryFactory;

/**
 * Category Aggregation Builder
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Category implements AggregationInterface
{
    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Helper\Rule
     */
    private $helper;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Search\ContextInterface
     */
    private $context;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root
     */
    private $virtualCategoryRoot;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Api\Search\ContextInterface           $contextInterface    Search Context
     * @param \Smile\ElasticsuiteVirtualCategory\Helper\Rule                $helper              Rule Helper
     * @param \Magento\Store\Model\StoreManagerInterface                    $storeManager        Store Manager
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface              $categoryRepository  Category Repository
     * @param \Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root $virtualCategoryRoot Virtual Category Root
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Api\Search\ContextInterface $contextInterface,
        \Smile\ElasticsuiteVirtualCategory\Helper\Rule $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root $virtualCategoryRoot
    ) {
        $this->helper             = $helper;
        $this->context            = $contextInterface;
        $this->storeManager       = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->virtualCategoryRoot = $virtualCategoryRoot;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregationData(\Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute)
    {
        $bucketConfig = [];
        $facetQueries = $this->getFacetQueries();
        if (!empty($facetQueries)) {
            $bucketConfig = ['type' => BucketInterface::TYPE_QUERY_GROUP, 'name' => 'categories', 'queries' => $facetQueries];
        }

        return $bucketConfig;
    }

    /**
     * List of subcategories queries by category id.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface[]
     */
    private function getFacetQueries()
    {
        $category = $this->getCurrentCategory();
        // Use the root category to display facets if configured this way.
        if ($this->virtualCategoryRoot->useVirtualRootCategorySubtree($category)) {
            $category = $this->virtualCategoryRoot->getVirtualCategoryRoot($category);
        }

        return $this->helper->loadUsingCache($category, 'getSearchQueriesByChildren');
    }

    /**
     * Retrieve current category from search context or instantiate the default root of current store.
     *
     * @return \Magento\Catalog\Api\Data\CategoryInterface
     */
    private function getCurrentCategory()
    {
        $category = $this->context->getCurrentCategory();

        if (null === $category) {
            $category = $this->categoryRepository->get(
                $this->storeManager->getStore()->getRootCategoryId(),
                $this->storeManager->getStore()->getId()
            );
        }

        return $category;
    }
}
