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
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalogRule\Model\Data\ConditionFactory as ConditionDataFactory ;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteVirtualCategory\Api\Data\VirtualRuleInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder;
use Smile\ElasticsuiteVirtualCategory\Helper\Config;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\DependencyCollector;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\RootCategoryProvider;
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
     * @var QueryCache
     */
    private $queryCache;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var DependencyCollector
     */
    private $dependencyCollector;

    /**
     * @var RootCategoryProvider
     */
    private $rootCategoryProvider;

    /**
     * Deprecated, unused: virtual root categories are loaded once per process by the root categories provider.
     *
     * @var Category[]
     * @see RootCategoryProvider
     */
    protected $instances = [];

    /**
     * Deprecated, unused: the in-process cache of the queries is held by the virtual rules query cache.
     *
     * @var array
     * @see QueryCache
     */
    protected static $localCache = [];

    /**
     * Constructor.
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param Context                   $context                   Context.
     * @param Registry                  $registry                  Registry.
     * @param FormFactory               $formFactory               Form factory.
     * @param TimezoneInterface         $localeDate                Locale date.
     * @param CombineConditionFactory   $combineConditionsFactory  Search engine rule (combine) condition factory.
     * @param ConditionDataFactory      $conditionDataFactory      Condition Data factory.
     * @param ProductConditionFactory   $productConditionsFactory  Search engine rule (product) condition factory.
     * @param QueryFactory              $queryFactory              Search query factory.
     * @param CategoryFactory           $categoryFactory           Product category factorty.
     * @param CollectionFactory         $categoryCollectionFactory Virtual categories collection factory.
     * @param QueryBuilder              $queryBuilder              Search rule query builder.
     * @param StoreManagerInterface     $storeManagerInterface     Store Manager
     * @param Session                   $customerSession           Deprecated, unused: the customer group is handled
     *                                                             by the query cache.
     * @param CacheInterface            $cache                     Deprecated, unused: the queries are cached by the
     *                                                             query cache.
     * @param Config                    $config                    Virtual category configuration.
     * @param array                     $data                      Additional data.
     * @param QueryCache|null           $queryCache                Virtual rules query cache.
     * @param DependencyCollector|null  $dependencyCollector       Virtual rules dependency collector.
     * @param RootCategoryProvider|null $rootCategoryProvider      Virtual root categories provider.
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
        array                   $data = [],
        ?QueryCache             $queryCache = null,
        ?DependencyCollector    $dependencyCollector = null,
        ?RootCategoryProvider   $rootCategoryProvider = null
    ) {
        $this->queryFactory              = $queryFactory;
        $this->productConditionsFactory  = $productConditionsFactory;
        $this->categoryFactory           = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->queryBuilder              = $queryBuilder;
        $this->storeManager              = $storeManagerInterface;
        $this->config                    = $config;
        $this->queryCache                = $queryCache ?: ObjectManager::getInstance()->get(QueryCache::class);
        $this->dependencyCollector       = $dependencyCollector ?: ObjectManager::getInstance()->get(
            DependencyCollector::class
        );
        $this->rootCategoryProvider      = $rootCategoryProvider ?: ObjectManager::getInstance()->get(
            RootCategoryProvider::class
        );

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
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return QueryInterface|null
     */
    public function getCategorySearchQuery($category, $excludedCategories = []): ?QueryInterface
    {
        \Magento\Framework\Profiler::start('ES:Virtual Rule ' . __FUNCTION__);
        $categoryId = (int) (!is_object($category) ? $category : $category->getId());
        $storeId = !is_object($category) ? $this->getStoreId() : $category->getStoreId();
        $storeId = $storeId ?: $this->storeManager->getStore()->getId();

        // The store root category has no page of its own: wherever it is used, it stands for the store tree products.
        if ($this->isStoreRootUsedAsStandardCategory($categoryId, (int) $storeId)) {
            $query = $this->getStoreRootQuery($category);
            \Magento\Framework\Profiler::stop('ES:Virtual Rule ' . __FUNCTION__);

            return $query;
        }

        // Used by another category (as its virtual root or in its rule): an expansion boundary is never expanded.
        // Checked before the local cache, which may hold its full query (eg. built for its own page).
        if (!empty($excludedCategories) && $this->isExpansionBoundary($categoryId)) {
            $query = $this->getExpansionBoundaryQuery($category);
            // Saving the boundary invalidates the queries embedding it.
            $this->dependencyCollector->addCategoryIds([$categoryId]);
            \Magento\Framework\Profiler::stop('ES:Virtual Rule ' . __FUNCTION__);

            return $query;
        }

        // The contexts (eg. the customer group) are only added to the key of the queries depending on them, by the
        // query cache.
        $cacheKey = implode(
            '|',
            array_filter(
                [
                    __FUNCTION__,
                    $storeId,
                    $categoryId,
                    (int) $this->config->isForceZeroResultsForDisabledCategoriesEnabled($storeId),
                    (int) $this->config->isStoreRootUsedAsStandardCategory(),
                    $this->config->getVirtualRulesExpansionBoundariesCacheKey(),
                ],
                function ($keyPart) {
                    return $keyPart !== null;
                }
            )
        );

        // Every category read while building the query is recorded as a dependency (and so a cache tag) of it.
        $this->dependencyCollector->start();
        $this->dependencyCollector->addCategoryIds([$categoryId]);

        $query       = null;
        $rootPathIds = [];

        try {
            // If the category is not an object, it can't be in a "draft" mode. Draft rules are not shared.
            $useSharedCache = !is_object($category) || !$category->getHasDraftVirtualRule();
            $cacheData      = $this->getFromCache($cacheKey, $useSharedCache);
            $isBuilt        = false;

            if ($cacheData !== false && $this->leaveOutForRoot($cacheData['data']['rootPathIds'], $excludedCategories)) {
                // Same outcome as building it: left out since the query of its virtual root is being built.
                $query = null;
            } elseif ($cacheData !== false && $this->isReusableInContext($cacheData['data']['embedded'], $excludedCategories)) {
                $query = $cacheData['data']['query'];
                $this->dependencyCollector->addEmbedded($cacheData['data']['embedded']);
                $this->dependencyCollector->addCacheTags($cacheData['tags']);
                $this->dependencyCollector->setExpiration($cacheData['expiresAt']);
                $this->dependencyCollector->addContextDependencies($cacheData['context']);
            } else {
                if (!is_object($category)) {
                    $category = $this->categoryFactory->create()->setStoreId($this->getStoreId())->load($category);
                }
                $query   = $this->buildCategorySearchQuery($category, $excludedCategories);
                $isBuilt = true;
                if ($query !== null) {
                    // Embedding it only depends on its virtual root not being built (see leaveOutForRoot()).
                    $rootPathIds = $this->getVirtualRootPathIds($category);
                    $this->dependencyCollector->addEmbedded(array_merge([$categoryId], $rootPathIds));
                }
            }
        } finally {
            if ($query === null) {
                // Left out: nothing of it is embedded in the query being built.
                $this->dependencyCollector->discardEmbedded();
            }
            $embedded   = $this->dependencyCollector->getEmbedded();
            $isExcluded = in_array($categoryId, $excludedCategories);
            if (!$isExcluded) {
                // The category may have been left out of the sub-queries built meanwhile: it is now complete.
                $this->dependencyCollector->resolveTruncation([$categoryId]);
            }
            // A query which left out categories still being built only makes sense in the current context.
            $isCacheable         = !$isExcluded && $this->dependencyCollector->isCacheable();
            $expiresAt           = $this->dependencyCollector->getExpiration();
            $contextDependencies = $this->dependencyCollector->getContextDependencies();
            $cacheTags           = $this->dependencyCollector->stop();
        }

        if ($isCacheable && $isBuilt && $query !== null) {
            // The virtual root path is kept along with the query: it is needed to leave it out when served from the cache.
            $cacheData = ['query' => $query, 'rootPathIds' => $rootPathIds, 'embedded' => $embedded];
            $this->queryCache->save($cacheKey, $cacheData, $cacheTags, $expiresAt, $contextDependencies, $useSharedCache);
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
        $this->dependencyCollector->addCategoryIds([$rootCategory->getId()]);

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
     * @SuppressWarnings(PHPMD.ElseExpression)
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
        } else {
            $this->dependencyCollector->addTruncation([$category->getId()]);
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

        if ($rootCategory && $rootCategory->getId()) {
            $this->dependencyCollector->addCategoryIds([$rootCategory->getId()]);
        }

        return $rootCategory;
    }

    /**
     * Get a virtual root category, loaded once for all the virtual rules using it.
     *
     * @param int      $rootCategoryId Root category id.
     * @param int|null $storeId        Store id.
     * @return CategoryInterface
     * @throws NoSuchEntityException
     */
    private function getRootCategory(int $rootCategoryId, ?int $storeId = null)
    {
        return $this->rootCategoryProvider->get($rootCategoryId, $storeId);
    }

    /**
     * Whether a category is the root category of a store, to be used as a standard category.
     *
     * @param int $categoryId Category id.
     * @param int $storeId    Store id.
     *
     * @return bool
     */
    private function isStoreRootUsedAsStandardCategory(int $categoryId, int $storeId): bool
    {
        return $this->config->isStoreRootUsedAsStandardCategory()
            && $categoryId === (int) $this->storeManager->getStore($storeId)->getRootCategoryId();
    }

    /**
     * Query of the store root category: the products assigned to the categories of the store tree, without expanding
     * the virtual categories of the store.
     *
     * @param CategoryInterface|int $category Store root category.
     *
     * @return QueryInterface|null
     */
    private function getStoreRootQuery($category): ?QueryInterface
    {
        if (!is_object($category)) {
            try {
                $category = $this->getRootCategory((int) $category, $this->getStoreId());
            } catch (NoSuchEntityException $e) {
                return null;
            }
        }

        return $this->getStandardCategoryQuery($category);
    }

    /**
     * Whether a category is an expansion boundary.
     *
     * @param int $categoryId Category id.
     *
     * @return bool
     */
    private function isExpansionBoundary(int $categoryId): bool
    {
        return in_array($categoryId, $this->config->getVirtualRulesExpansionBoundaries(), true);
    }

    /**
     * Query of an expansion boundary used by another category: its own products only (and the ones of its
     * subcategories for an anchor), without the products added by its virtual subcategories.
     * Disabled categories are handled as in buildCategorySearchQuery().
     *
     * @param CategoryInterface|int $category Expansion boundary category.
     *
     * @return QueryInterface|null
     */
    private function getExpansionBoundaryQuery($category): ?QueryInterface
    {
        $query = null;

        if (!is_object($category)) {
            try {
                $category = $this->getRootCategory((int) $category, $this->getStoreId());
            } catch (NoSuchEntityException $e) {
                return null;
            }
        }

        if ($category->getIsActive()) {
            $query = $this->getStandardCategoryQuery($category);
        } elseif ($this->config->isForceZeroResultsForDisabledCategoriesEnabled($this->getStoreId())) {
            $query = $this->getNoResultsQuery();
        }

        return $query;
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
        if ($this->leaveOutForRoot($this->getVirtualRootPathIds($category), $excludedCategories)) {
            return null;
        }

        $rootCategory = $this->getVirtualRootCategory($category);
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
        $childrenCategories    = $this->getChildrenCategories($category);
        $childrenCategoriesIds = [];

        if ($query !== null && $childrenCategories->getSize() > 0) {
            $queryParams = ['should' => [$query], 'cached' => empty($excludedCategories)];
            $expandedIds = [];

            foreach ($childrenCategories as $childrenCategory) {
                if (!empty(array_intersect($childrenCategory->getPathIds(), $expandedIds))) {
                    // Already embedded by the query of a virtual ancestor, which expands its own descendants.
                    continue;
                }
                if (in_array($childrenCategory->getId(), $excludedCategories)) {
                    // Category whose query is being built: left out to avoid infinite recursion. If its virtual root
                    // is being built as well, the truncation is recorded against the root instead (see
                    // leaveOutForRoot()).
                    if (!$this->leaveOutForRoot($this->getVirtualRootPathIds($childrenCategory), $excludedCategories)) {
                        $this->dependencyCollector->addTruncation([$childrenCategory->getId()]);
                    }
                } elseif (((bool) $childrenCategory->getIsVirtualCategory()) === true) {
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
                        // An expansion boundary query does not expand its descendants.
                        if (!$this->isExpansionBoundary((int) $childrenCategory->getId())) {
                            $expandedIds[] = $childrenCategory->getId();
                        }
                    }
                } else {
                    $childrenCategoriesIds[] = $childrenCategory->getId();
                }
            }

            if (!empty($childrenCategoriesIds)) {
                $this->dependencyCollector->addEmbedded($childrenCategoriesIds);
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
     * @param CategoryInterface $category Category.
     *
     * @return Collection
     */
    private function getChildrenCategories(CategoryInterface $category): Collection
    {
        $storeId            = $category->getStoreId();
        $categoryCollection = $this->categoryCollectionFactory->create()->setStoreId($storeId);

        $categoryCollection->addIsActiveFilter()->addPathFilter(sprintf('%s/.*', $category->getPath()));
        // Shallowest first: a virtual category is handled before its own descendants.
        $categoryCollection->setOrder('level', 'ASC');

        if (((bool) $category->getIsVirtualCategory()) === false) {
            $categoryCollection->addFieldToFilter('is_virtual_category', '1');
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
     * Get a category query entry from the cache. Entries not matching the expected format are considered as missing.
     *
     * @param string $cacheKey       Cache key.
     * @param bool   $useSharedCache Whether to look into the shared cache, and not only into the in-process one.
     *
     * @return array|false
     */
    private function getFromCache(string $cacheKey, bool $useSharedCache)
    {
        $cacheData = $this->queryCache->load($cacheKey, $useSharedCache);

        if ($cacheData !== false
            && (!is_array($cacheData['data'])
                || !isset($cacheData['data']['query'], $cacheData['data']['rootPathIds'], $cacheData['data']['embedded']))
        ) {
            $cacheData = false;
        }

        return $cacheData;
    }

    /**
     * Ids of the path of the virtual root of a category (the root included), if its query is built on top of it.
     *
     * @param CategoryInterface $category Category.
     *
     * @return int[]
     */
    private function getVirtualRootPathIds(CategoryInterface $category): array
    {
        $rootPathIds = [];

        if ($category->getIsVirtualCategory() && $category->getIsActive()) {
            $rootCategory = $this->getVirtualRootCategory($category);
            if ($rootCategory && $rootCategory->getPath()) {
                $rootPathIds = array_map('intval', explode('/', (string) $rootCategory->getPath()));
            }
        }

        return $rootPathIds;
    }

    /**
     * Leave out a category if its virtual root, or one of the root ancestors, is being built: all its products are
     * already part of the query being built.
     * For example, with the following category tree:
     * - Category A (static)
     * -   - Category B (static)
     * -       - Category C (virtual with category B as root)
     * When building the query of the category A, the category C does not need to be built.
     *
     * The left out categories are recorded as a truncation of the query being built, as well as the virtual root
     * as a dependency, the same way whether the category query is built or served from the cache.
     *
     * @param int[] $rootPathIds        Ids of the path of the category virtual root.
     * @param array $excludedCategories Categories being built.
     *
     * @return bool Whether the category is left out.
     */
    private function leaveOutForRoot(array $rootPathIds, array $excludedCategories): bool
    {
        $leftOutBy = array_intersect($excludedCategories, $rootPathIds);

        if (!empty($leftOutBy)) {
            $this->dependencyCollector->addCategoryIds([end($rootPathIds)]);
            $this->dependencyCollector->addTruncation($leftOutBy);
        }

        return !empty($leftOutBy);
    }

    /**
     * Whether a cached query can be reused while building the queries of the excluded categories.
     * It cannot if it embeds a category being built: that category would be left out of it in the current context.
     * Merely depending on such a category (eg. a virtual child left out of it) does not prevent reusing it.
     *
     * @param int[] $embedded           Categories embedded in the cached query.
     * @param array $excludedCategories Categories being built.
     *
     * @return bool
     */
    private function isReusableInContext(array $embedded, array $excludedCategories): bool
    {
        return empty($excludedCategories) || empty(array_intersect($embedded, $excludedCategories));
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
