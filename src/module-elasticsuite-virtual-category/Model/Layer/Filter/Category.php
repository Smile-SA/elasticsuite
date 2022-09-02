<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\Layer\Filter;

use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteCatalog\Model\Search\Request\Field\Mapper as RequestFieldMapper;

/**
 * Product category filter implementation using virtual categories.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Category extends \Smile\ElasticsuiteCatalog\Model\Layer\Filter\Category
{
    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\Category\Filter\Provider
     */
    private $filterProvider;

    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root
     */
    private $virtualCategoryRoot;

    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\Url
     */
    private $urlModel;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory                   $filterItemFactory   Filter item factory.
     * @param \Magento\Store\Model\StoreManagerInterface                        $storeManager        Store manager.
     * @param \Magento\Catalog\Model\Layer                                      $layer               Search layer.
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder              $itemDataBuilder     Item data builder.
     * @param \Magento\Framework\Escaper                                        $escaper             HTML escaper.
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory  $dataProviderFactory Data provider.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface                $scopeConfig         Scope config.
     * @param \Smile\ElasticsuiteCore\Api\Search\ContextInterface               $context             Search context.
     * @param \Smile\ElasticsuiteVirtualCategory\Model\Category\Filter\Provider $filterProvider      Category Filter provider.
     * @param RequestFieldMapper                                                $requestFieldMapper  Search request field mapper.
     * @param \Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root     $virtualCategoryRoot Virtual Category Root.
     * @param \Smile\ElasticsuiteVirtualCategory\Model\Url                      $urlModel            Url Model.
     * @param boolean                                                           $useUrlRewrites      Uses URLs rewrite for rendering.
     * @param array                                                             $data                Custom data.
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Escaper $escaper,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory $dataProviderFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Smile\ElasticsuiteCore\Api\Search\ContextInterface $context,
        \Smile\ElasticsuiteVirtualCategory\Model\Category\Filter\Provider $filterProvider,
        RequestFieldMapper $requestFieldMapper,
        \Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root $virtualCategoryRoot,
        \Smile\ElasticsuiteVirtualCategory\Model\Url $urlModel,
        $useUrlRewrites = false,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $escaper,
            $dataProviderFactory,
            $scopeConfig,
            $context,
            $requestFieldMapper,
            $useUrlRewrites,
            $data
        );

        $this->filterProvider = $filterProvider;
        $this->virtualCategoryRoot = $virtualCategoryRoot;
        $this->urlModel = $urlModel;
    }

    /**
     * {@inheritDoc}
     */
    protected function applyCategoryFilterToCollection(\Magento\Catalog\Api\Data\CategoryInterface $category)
    {
        $query = $this->getFilterQuery();

        if ($query !== null) {
            $this->getLayer()->getProductCollection()->addQueryFilter($query);
        }

        return $this;
    }

    /**
     * Retrieve currently selected category children categories.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection|\Magento\Catalog\Model\Category[]
     */
    protected function getChildrenCategories()
    {
        if ($this->childrenCategories === null) {
            $currentCategory = $this->getDataProvider()->getCategory();
            $this->childrenCategories = $currentCategory->getChildrenCategories();
            // Use the root category to retrieve children if needed.
            if ($this->virtualCategoryRoot->useVirtualRootCategorySubtree($currentCategory)) {
                $rootCategory = $this->virtualCategoryRoot->getVirtualCategoryRoot($currentCategory);
                if ($rootCategory->getId()) {
                    $this->childrenCategories = $rootCategory->getChildrenCategories();
                    $this->childrenCategories->clear()->addFieldToFilter(
                        'entity_id',
                        ['neq' => $currentCategory->getId()]
                    );
                }
            }
        }

        return $this->childrenCategories;
    }

    /**
     * Retrieve Category Url to build filter
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $childCategory Category.
     *
     * @return string
     */
    protected function getCategoryFilterUrl($childCategory)
    {
        $url = parent::getCategoryFilterUrl($childCategory);

        $currentCategory = $this->getDataProvider()->getCategory();

        $appliedRootCategory = $this->getDataProvider()->getAppliedRootCategory();

        // Use the root category to retrieve children categories Url if needed.
        if ($this->virtualCategoryRoot->useVirtualRootCategorySubtree($currentCategory)) {
            $url = $this->urlModel->getVirtualCategorySubtreeUrl($currentCategory, $childCategory);
        } elseif ($appliedRootCategory) {
            // Occurs when navigating through the subtree of a virtual root category.
            $url = $this->urlModel->getVirtualCategorySubtreeUrl($appliedRootCategory, $childCategory);
        }

        return $url;
    }

    /**
     * Current category filter query.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    private function getFilterQuery()
    {
        $category            = $this->getDataProvider()->getCategory();
        $appliedRootCategory = $this->getDataProvider()->getAppliedRootCategory();
        $categoryFilter      = $this->filterProvider->getQueryFilter($this->getDataProvider()->getCategory());

        if ($appliedRootCategory && $appliedRootCategory->getId()) {
            $categoryFilter = $category->getVirtualRule()->mergeCategoryQueries([$category, $appliedRootCategory]);
        }

        return $categoryFilter;
    }
}
