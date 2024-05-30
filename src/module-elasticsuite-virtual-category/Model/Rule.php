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
namespace Smile\ElasticsuiteVirtualCategory\Model;

use Magento\Catalog\Model\Category;
use Magento\Customer\Model\Session;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalogRule\Model\Data\ConditionFactory as ConditionDataFactory ;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteVirtualCategory\Api\Data\VirtualRuleInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder;
use Smile\ElasticsuiteVirtualCategory\Helper\Config;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\ProductFactory as ProductConditionFactory;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\CombineFactory as CombineConditionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Framework\Model\Context;

/**
 * Virtual category rule.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Rule extends \Smile\ElasticsuiteCatalogRule\Model\Rule implements VirtualRuleInterface
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var ProductConditionFactory
     */
    private $productConditionsFactory;

    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var CacheInterface
     */
    private $sharedCache;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Category[]
     */
    protected $instances = [];

    /**
     * @var array
     */
    protected static $localCache = [];

    /**
     * Constructor.
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param Context                 $context                   Context.
     * @param Registry                $registry                  Registry.
     * @param FormFactory             $formFactory               Form factory.
     * @param TimezoneInterface       $localeDate                Locale date.
     * @param CombineConditionFactory $combineConditionsFactory  Search engine rule (combine) condition factory.
     * @param ConditionDataFactory    $conditionDataFactory      Condition Data factory.
     * @param ProductConditionFactory $productConditionsFactory  Search engine rule (product) condition factory.
     * @param QueryFactory            $queryFactory              Search query factory.
     * @param CategoryFactory         $categoryFactory           Product category factorty.
     * @param CollectionFactory       $categoryCollectionFactory Virtual categories collection factory.
     * @param QueryBuilder            $queryBuilder              Search rule query builder.
     * @param StoreManagerInterface   $storeManagerInterface     Store Manager
     * @param Session                 $customerSession           Customer session.
     * @param CacheInterface          $cache                     Cache.
     * @param Config                  $config                    Virtual category configuration.
     * @param array                   $data                      Additional data.
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory             $formFactory,
        TimezoneInterface       $localeDate,
        CombineConditionFactory $combineConditionsFactory,
        ConditionDataFactory    $conditionDataFactory,
        ProductConditionFactory $productConditionsFactory,
        QueryFactory            $queryFactory,
        CategoryFactory         $categoryFactory,
        CollectionFactory       $categoryCollectionFactory,
        QueryBuilder            $queryBuilder,
        StoreManagerInterface   $storeManagerInterface,
        Session                 $customerSession,
        CacheInterface          $cache,
        Config                  $config,
        array                   $data = []
    ) {
        $this->queryFactory              = $queryFactory;
        $this->productConditionsFactory  = $productConditionsFactory;
        $this->categoryFactory           = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->queryBuilder              = $queryBuilder;
        $this->storeManager              = $storeManagerInterface;
        $this->customerSession           = $customerSession;
        $this->sharedCache               = $cache;
        $this->config                    = $config;

        parent::__construct($context, $registry, $formFactory, $localeDate, $combineConditionsFactory, $conditionDataFactory, $data);
    }

    /**
     * Implementation of __toString().
     * This one is mandatory to ensure the object is properly handled when it get sent "as is" to a DB query.
     * This occurs especially in @see \Magento\Catalog\Model\Indexer\Category\Flat\Action\Rows::reindex() (line 86 & 98)
     *
     * @return string
     */
    public function __toString(): string
    {
        return json_encode($this->getConditions()->asArray());
    }

    /**
     * Get search query by category from cache or build it.
     *
     * @param CategoryInterface|int $category           Search category.
     * @param array                 $excludedCategories Categories that should not be used into search query building.
     *                                                  Used to avoid infinite recursion while building virtual categories rules.
     *
     * @codingStandardsIgnoreStart
     * @TODO: manage cache in this file for getSearchQueriesByChildren,
     * remove the \Smile\ElasticsuiteVirtualCategory\Helper\Rule class,
     * do not use the $excludedCategories parameters to check if the category rule has been calculated, but use the local cache.
     * @codingStandardsIgnoreEnd
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @return QueryInterface|null
     */
    public function getCategorySearchQuery($category, $excludedCategories = []): ?QueryInterface
    {
        \Magento\Framework\Profiler::start('ES:Virtual Rule ' . __FUNCTION__);
        $categoryId = (int) (!is_object($category) ? $category : $category->getId());
        $storeId = !is_object($category) ? $this->getStoreId() : $category->getStoreId();
        $cacheKey = implode(
            '|',
            [
                __FUNCTION__,
                $storeId,
                $categoryId,
                $this->customerSession->getCustomerGroupId(),
                $this->config->isForceZeroResultsForDisabledCategoriesEnabled($storeId),
            ]
        );

        $query = $this->getFromLocalCache($categoryId);

        // If the category is not an object, it can't be in a "draft" mode.
        if ($query === false && (!is_object($category) || !$category->getHasDraftVirtualRule())) {
            // Due to the fact we serialize/unserialize completely pre-built queries as object.
            // We cannot use any implementation of SerializerInterface.
            $query = $this->sharedCache->load($cacheKey);
            $query = $query ? unserialize($query) : false;
        }

        if ($query === false) {
            if (!is_object($category)) {
                $category = $this->categoryFactory->create()->setStoreId($this->getStoreId())->load($category);
            }
            $query = $this->buildCategorySearchQuery($category, $excludedCategories);

            if (!$category->getHasDraftVirtualRule() && $query !== null && !in_array($categoryId, $excludedCategories)) {
                $cacheData   = serialize($query);
                $this->sharedCache->save($cacheData, $cacheKey, $category->getCacheTags());
            }
        }

        if (!in_array($categoryId, $excludedCategories)) {
            $this->saveInLocalCache($categoryId, $query);
        }

        \Magento\Framework\Profiler::stop('ES:Virtual Rule ' . __FUNCTION__);

        return $query;
    }

    /**
     * Retrieve search queries of children categories.
     *
     * @param CategoryInterface $rootCategory Root category.
     *
     * @return QueryInterface[]
     */
    public function getSearchQueriesByChildren(CategoryInterface $rootCategory): array
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
                $childQuery = $this->getCategorySearchQuery($category);
                if ($childQuery !== null) {
                    $queries[$category->getId()] = $childQuery;
                }
            }
        }

        return $queries;
    }

    /**
     * {@inheritDoc}
     */
    public function getCondition()
    {
        $conditions = $this->getConditions()->asArray();

        return $this->arrayToConditionDataModel($conditions);
    }

    /**
     * {@inheritDoc}
     */
    public function setCondition($condition)
    {
        $this->getConditions()->setConditions([])->loadArray($this->dataModelToArray($condition));

        return $this;
    }

    /**
     * Combine several category queries
     *
     * @param CategoryInterface[] $categories The categories
     *
     * @return QueryInterface
     */
    public function mergeCategoryQueries(array $categories)
    {
        $queries = [];

        foreach ($categories as $category) {
            $queries[] = $this->getCategorySearchQuery($category);
        }

        return $this->queryFactory->create(QueryInterface::TYPE_BOOL, ['must' => $queries]);
    }

    /**
     * Build search query by category.
     *
     * @param CategoryInterface|int $category           Search category.
     * @param array                 $excludedCategories Categories that should not be used into search query building.
     *
     * @return QueryInterface|null
     */
    private function buildCategorySearchQuery($category, $excludedCategories = []): ?QueryInterface
    {
        $query = null;

        if (!in_array($category->getId(), $excludedCategories)) {
            $excludedCategories[] = $category->getId();

            if (!$category->getIsActive()
                && $this->config->isForceZeroResultsForDisabledCategoriesEnabled($this->getStoreId())) {
                return $this->getNoResultsQuery();
            }

            if ((bool) $category->getIsVirtualCategory() && $category->getIsActive()) {
                $query = $this->getVirtualCategoryQuery($category, $excludedCategories);
            } elseif ($category->getId() && $category->getIsActive()) {
                $query = $this->getStandardCategoryQuery($category, $excludedCategories);
            }
            if ($query && $category->hasChildren()) {
                $query = $this->addChildrenQueries($query, $category, $excludedCategories);
            }
        }

        return $query;
    }

    /**
     * Load the root category used for a virtual category.
     *
     * @param CategoryInterface $category Virtual category.
     *
     * @return CategoryInterface|null
     * @throws NoSuchEntityException
     */
    private function getVirtualRootCategory(CategoryInterface $category): ?CategoryInterface
    {
        $storeId      = $this->getStoreId();
        $rootCategory = null;

        if ($category->getVirtualCategoryRoot() !== null && !empty($category->getVirtualCategoryRoot())) {
            $rootCategoryId = $category->getVirtualCategoryRoot();
            try {
                $rootCategory = $this->getRootCategory($rootCategoryId, $storeId);
            } catch (NoSuchEntityException $e) {
                $rootCategory = null;
            }
        }

        if ($rootCategory && $rootCategory->getId()
            && ($rootCategory->getLevel() < 1 || (int) $rootCategory->getId() === (int) $this->getTreeRootCategoryId($category))
        ) {
            $rootCategory = null;
        }

        return $rootCategory;
    }

    /**
     * Get info about category by category id.
     * This code uses a local cache for better performance.
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param int      $rootCategoryId Root category id.
     * @param int|null $storeId        Store id.
     * @return CategoryInterface
     * @throws NoSuchEntityException
     */
    private function getRootCategory(int $rootCategoryId, int $storeId = null)
    {
        $cacheKey = $storeId ?? 'all';
        if (!isset($this->instances[$rootCategoryId][$cacheKey])) {
            $rootCategory = $this->categoryFactory->create();
            if (null !== $storeId) {
                $rootCategory->setStoreId($storeId);
            }
            $rootCategory->load($rootCategoryId);
            if (!$rootCategory->getId()) {
                throw NoSuchEntityException::singleField('id', $rootCategoryId);
            }
            $this->instances[$rootCategoryId][$cacheKey] = $rootCategory;
        }

        return $this->instances[$rootCategoryId][$cacheKey];
    }

    /**
     * Transform a category in query rule.
     *
     * @param CategoryInterface $category           Category.
     * @param array             $excludedCategories Categories ignored in subquery filters.
     *
     * @return QueryInterface
     */
    private function getStandardCategoryQuery(CategoryInterface $category, $excludedCategories = []): QueryInterface
    {
        $query = $this->getStandardCategoriesQuery([$category->getId()], $excludedCategories);
        $query->setName(sprintf('(%s) standard category [%s]:%d', $category->getPath(), $category->getName(), $category->getId()));

        return $query;
    }

    /**
     * Transform a category ids list in query rule.
     *
     * @param array $categoryIds        Categories included in the query.
     * @param array $excludedCategories Categories ignored in subquery filters.
     *
     * @return QueryInterface
     */
    private function getStandardCategoriesQuery(array $categoryIds, $excludedCategories): QueryInterface
    {
        $conditionsParams  = ['data' => ['attribute' => 'category_ids', 'operator' => '()', 'value' => $categoryIds]];
        $categoryCondition = $this->productConditionsFactory->create($conditionsParams);

        return $this->queryBuilder->getSearchQuery($categoryCondition, $excludedCategories);
    }

    /**
     * Transform the virtual category into a QueryInterface used for filtering.
     *
     * @param CategoryInterface $category           Virtual category.
     * @param array             $excludedCategories Category already used into the building stack. Avoid short circuit.
     *
     * @return QueryInterface
     */
    private function getVirtualCategoryQuery(
        CategoryInterface $category,
        $excludedCategories = []
    ): ?QueryInterface {
        $rootCategory = $this->getVirtualRootCategory($category);
        // If the root category of the current virtual category has already been computed (exist in $excludedCategories)
        // or if a parent of the root category of the current category has already been computed we don't need
        // to compute the rule. All the product will already been present.
        // For example, if you have the following category tree:
        // - Category A (static)
        // -   - Category B (static)
        // -       - Category C (virtual with category B as root)
        // When you compute the rule of the category A you do not need to compute the rule of the category C
        // as all the product will be there.
        if ($rootCategory
            && $rootCategory->getPath()
            && array_intersect(explode('/', (string) $rootCategory->getPath()), $excludedCategories)
        ) {
            return null;
        }

        $query = $category->getVirtualRule()->getConditions()->getSearchQuery($excludedCategories);
        if ($query instanceof QueryInterface) {
            $queryName = sprintf('(%s) virtual category [%s]:%d', $category->getPath(), $category->getName(), $category->getId());
            $query->setName(($query->getName() !== '') ? $queryName . " => " . $query->getName() : $queryName);
        }
        if ($rootCategory && $rootCategory->getId()) {
            $rootQuery = $this->getCategorySearchQuery($rootCategory, $excludedCategories);
            if ($rootQuery) {
                $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, ['must' => array_filter([$query, $rootQuery])]);
                $query->setName(
                    sprintf(
                        '(%s) virtual category [%s]:%d and its virtual root',
                        $category->getPath(),
                        $category->getName(),
                        $category->getId()
                    )
                );
            }
        }

        return $query;
    }

    /**
     * Append children queries to the rule.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param QueryInterface|NULL $query              Base query.
     * @param CategoryInterface   $category           Current category.
     * @param array               $excludedCategories Category already used into the building stack. Avoid short circuit.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    private function addChildrenQueries($query, CategoryInterface $category, $excludedCategories = []): QueryInterface
    {
        $childrenCategories    = $this->getChildrenCategories($category, $excludedCategories);
        $childrenCategoriesIds = [];

        if ($query !== null && $childrenCategories->getSize() > 0) {
            $queryParams = ['should' => [$query], 'cached' => empty($excludedCategories)];

            foreach ($childrenCategories as $childrenCategory) {
                if (((bool) $childrenCategory->getIsVirtualCategory()) === true) {
                    $childrenQuery = $this->getCategorySearchQuery($childrenCategory, $excludedCategories);
                    if ($childrenQuery !== null) {
                        $childrenQuery->setName(
                            sprintf(
                                '(%s) child virtual category [%s]:%d',
                                $childrenCategory->getPath(),
                                $childrenCategory->getName(),
                                $childrenCategory->getId()
                            )
                        );
                        $queryParams['should'][] = $childrenQuery;
                    }
                } else {
                    $childrenCategoriesIds[] = $childrenCategory->getId();
                }
            }

            if (!empty($childrenCategoriesIds)) {
                $standardChildrenQuery = $this->getStandardCategoriesQuery($childrenCategoriesIds, $excludedCategories);
                $standardChildrenQuery->setName(
                    sprintf(
                        '(%s) standard children of virtual category [%s]:%d',
                        $category->getPath(),
                        $category->getName(),
                        $category->getId()
                    )
                );

                $queryParams['should'][] = $standardChildrenQuery;
            }

            if (count($queryParams['should']) > 1) {
                $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, $queryParams);
                $query->setName(
                    sprintf(
                        '(%s) category [%s]:%d and its children',
                        $category->getPath(),
                        $category->getName(),
                        $category->getId()
                    )
                );
            }
        }

        return $query;
    }

    /**
     * Returns the list of the virtual categories available under a category.
     *
     * @param CategoryInterface $category           Category.
     * @param array             $excludedCategories Category already used into the building stack. Avoid short circuit.
     *
     * @return Collection
     */
    private function getChildrenCategories(CategoryInterface $category, $excludedCategories = []): Collection
    {
        $storeId            = $category->getStoreId();
        $categoryCollection = $this->categoryCollectionFactory->create()->setStoreId($storeId);

        $categoryCollection->addIsActiveFilter()->addPathFilter(sprintf('%s/.*', $category->getPath()));

        if (((bool) $category->getIsVirtualCategory()) === false) {
            $categoryCollection->addFieldToFilter('is_virtual_category', '1');
        }

        if (!empty($excludedCategories)) {
            $categoryCollection->addAttributeToFilter('entity_id', ['nin' => $excludedCategories]);
        }

        $categoryCollection->addAttributeToSelect(['name', 'is_active', 'virtual_category_root', 'is_virtual_category', 'virtual_rule']);

        return $categoryCollection;
    }

    /**
     * Retrieve the root category id of the tree the category belongs to.
     *
     * @param CategoryInterface $category Category.
     *
     * @return int
     */
    private function getTreeRootCategoryId($category): int
    {
        $rootCategoryId = 0;

        $pathIds = $category->getPathIds();
        if (count($pathIds) > 1) {
            $rootCategoryId = (int) current(array_slice($pathIds, 1, 1));
        }

        return $rootCategoryId;
    }

    /**
     * Get category query from local cache.
     *
     * @param int $categoryId In of the category.
     * @return QueryInterface|bool|null
     */
    private function getFromLocalCache(int $categoryId)
    {
        return self::$localCache[$categoryId] ?? false;
    }

    /**
     * Save category query in local cache.
     *
     * @param int                      $categoryId Id of the category.
     * @param QueryInterface|bool|null $query      Query of the category.
     */
    private function saveInLocalCache(int $categoryId, $query): void
    {
        if ($query !== null) {
            self::$localCache[$categoryId] = $query;
        }
    }

    /**
     * Build a query that return zero products.
     *
     * @return QueryInterface
     */
    private function getNoResultsQuery(): QueryInterface
    {
        return $this->queryFactory->create(
            QueryInterface::TYPE_TERMS,
            ['field' => 'entity_id', 'values' => [0]]
        );
    }
}
