<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteVirtualCategory\Model;

use Smile\ElasticSuiteCatalogRule\Model\Rule;
use Smile\ElasticSuiteCore\Search\Request\QueryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;

/**
 * Virtual category rule.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile_ElasticSuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Rule extends \Smile\ElasticSuiteCatalogRule\Model\Rule
{
    /**
     * @var \Smile\ElasticSuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\ProductFactory
     */
    private $productConditionsFactory;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var \Smile\ElasticSuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder
     */
    private $queryBuilder;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param \Magento\Framework\Model\Context                                                         $context                   Context.
     * @param \Magento\Framework\Registry                                                              $registry                  Registry.
     * @param \Magento\Framework\Data\FormFactory                                                      $formFactory               Form factory.
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface                                     $localeDate                Locale date.
     * @param \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\CombineFactory                       $combineConditionsFactory  Search engine rule (combine) condition factory.
     * @param \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\ProductFactory                       $productConditionsFactory  Search engine rule (product) condition factory.
     * @param \Smile\ElasticSuiteCore\Search\Request\Query\QueryFactory                                $queryFactory              Search query factory.
     * @param \Magento\Catalog\Model\CategoryFactory                                                   $categoryFactory           Product category factorty.
     * @param \Smile\ElasticSuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory $categoryCollectionFactory Virtual categories collection factory.
     * @param \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder                 $queryBuilder              Search rule query builder.
     * @param array                                                                                    $data                      Additional data.
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\CombineFactory $combineConditionsFactory,
        \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\ProductFactory $productConditionsFactory,
        \Smile\ElasticSuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Smile\ElasticSuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory $categoryCollectionFactory,
        \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder $queryBuilder,
        array $data = []
    ) {
        $this->queryFactory              = $queryFactory;
        $this->productConditionsFactory  = $productConditionsFactory;
        $this->categoryFactory           = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->queryBuilder              = $queryBuilder;

        parent::__construct($context, $registry, $formFactory, $localeDate, $combineConditionsFactory, $data);
    }

    /**
     * Build search query by category.
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category           Search category.
     * @param array                                       $excludedCategories Categories that should not be used into search query building.
     *                                                                        Used to avoid infinite recursion while building virtual categories rules.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\QueryInterface
     */
    public function getCategorySearchQuery($category, $excludedCategories = [])
    {
        if (!is_object($category)) {
            $category = $this->categoryFactory->create()->setStoreId($this->getStoreId())->load($category);
        }

        $queryParams = [];

        if ((bool) $category->getIsVirtualCategory() && $category->getIsActive()) {
            $parentCategory = $this->getVirtualRootCategory($category);
            $excludedCategories[]  = $category->getId();

            $queryParams['must'][] = $this->getVirtualCategoryQuery($category, $excludedCategories);

            if ($parentCategory && $parentCategory->getId()) {
                $queryParams['must'][] = $this->getCategorySearchQuery($parentCategory, $excludedCategories);
            }
        } elseif ($category->getId() && $category->getIsActive()) {
            $queryParams['should'][] = $this->getStandardCategoryQuery($category);

            foreach ($this->getChildrenVirtualCategories($category, $excludedCategories) as $childrenCategory) {
                $queryParams['should'][] = $this->getVirtualCategoryQuery($childrenCategory, $excludedCategories);
            }
        }

        return $this->queryFactory->create(QueryInterface::TYPE_BOOL, $queryParams);
    }

    /**
     * Retrieve search queries of children categories.
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $rootCategory Root category.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\QueryInterface[]
     */
    public function getSearchQueriesByChildren(CategoryInterface $rootCategory)
    {
        $queries     = [];
        $childrenIds = $rootCategory->getResource()->getChildren($rootCategory, false);

        if (!empty($childrenIds)) {
            $storeId            = $this->getStoreId();
            $categoryCollection = $this->categoryCollectionFactory->create()->setStoreId($storeId);

            $categoryCollection
                ->setStoreId($this->getStoreId())
                ->addIsActiveFilter()
                ->addIdFilter($childrenIds)
                ->addAttributeToSelect(['virtual_category_root', 'is_virtual_category', 'virtual_rule']);

            foreach ($categoryCollection as $category) {
                $queries[$category->getId()] = $this->getCategorySearchQuery($category);
            }
        }

        return $queries;
    }

    /**
     * Load the root category used for a virtual category.
     *
     * @param CategoryInterface $category Virtual category.
     *
     * @return CategoryInterface
     */
    private function getVirtualRootCategory(CategoryInterface $category)
    {
        $storeId      = $this->getStoreId();
        $rootCategory = $this->categoryFactory->create()->setStoreId($storeId);

        if ($category->getVirtualCategoryRoot() !== null && !empty($category->getVirtualCategoryRoot())) {
            $rootCategoryId = explode('/', $category->getVirtualCategoryRoot())[1];
            $rootCategory->load($rootCategoryId);
        }

        return $rootCategory;
    }

    /**
     * Transform a category in query rule.
     *
     * @param CategoryInterface $category Category.
     *
     * @return QueryInterface
     */
    private function getStandardCategoryQuery(CategoryInterface $category)
    {
        $conditionsParams  = ['data' => ['attribute' => 'category_ids', 'operator' => '()', 'value' => $category->getId()]];
        $categoryCondition = $this->productConditionsFactory->create($conditionsParams);

        return $this->queryBuilder->getSearchQuery($categoryCondition);
    }

    /**
     * Transform the virtual category into a QueryInterface used for filtering.
     *
     * @param CategoryInterface $category           Virtual category.
     * @param array             $excludedCategories Category already used into the building stack. Avoid short circuit.
     *
     * @return QueryInterface
     */
    private function getVirtualCategoryQuery(CategoryInterface $category, $excludedCategories = [])
    {
        return $category->getVirtualRule()->getConditions()->getSearchQuery($excludedCategories);
    }

    /**
     * Returns the list of the virtual categories available under a category.
     *
     * @param CategoryInterface $category           Category.
     * @param array             $excludedCategories Category already used into the building stack. Avoid short circuit.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection;
     */
    private function getChildrenVirtualCategories(CategoryInterface $category, $excludedCategories = [])
    {
        $storeId            = $category->getStoreId();
        $categoryCollection = $this->categoryCollectionFactory->create()->setStoreId($storeId);

        $categoryCollection->addAttributeToFilter('is_virtual_category', 1)
            ->addAttributeToFilter('is_active', ['neq' => 0]) // Bug (Magento ?) when not using "neq".
            ->addPathFilter(sprintf('%s/.*', $category->getPath()));

        if (!empty($excludedCategories)) {
            $categoryCollection->addAttributeToFilter('entity_id', ['nin' => $excludedCategories]);
        }

        $categoryCollection->addAttributeToSelect(['is_active', 'virtual_category_root', 'is_virtual_category', 'virtual_rule']);

        return $categoryCollection;
    }
}
