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
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterfaceFactory;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ObjectManager;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\ReadHandler;

/**
 * Smile Elasticsuite virtual category cache helper.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Rule
{
    /**
     * Virtual rule methods managing their own cache, which must not be cached a second time by this helper.
     */
    private const SELF_CACHED_CALLBACKS = ['getCategorySearchQuery'];

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var QueryCache
     */
    private $queryCache;

    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\ReadHandler
     */
    private $readHandler;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterfaceFactory
     */
    private $categoryRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * Provider constructor.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param CacheInterface                                          $cache              Deprecated, unused: the data is
     *                                                                                    cached by the query cache.
     * @param \Magento\Customer\Model\Session                         $customerSession    Customer session.
     * @param ReadHandler                                             $readHandler        Rule read handler.
     * @param \Magento\Catalog\Api\CategoryRepositoryInterfaceFactory $categoryRepository Category factory.
     * @param Config                                                  $config             Virtual category configuration.
     * @param QueryCache|null                                         $queryCache         Virtual rules query cache.
     */
    public function __construct(
        CacheInterface $cache,
        \Magento\Customer\Model\Session $customerSession,
        ReadHandler $readHandler,
        CategoryRepositoryInterfaceFactory $categoryRepository,
        Config $config,
        ?QueryCache $queryCache = null
    ) {
        $this->customerSession = $customerSession;
        $this->readHandler = $readHandler;
        $this->categoryRepository = $categoryRepository;
        $this->config = $config;
        $this->queryCache = $queryCache ?: ObjectManager::getInstance()->get(QueryCache::class);
    }

    /**
     * Load data from the cache if exist. Use a callback on the current category if not yet present into the cache.
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param CategoryInterface $category Category
     * @param string            $callback Name of the virtual rule method to be used for actual loading.
     *
     * @return mixed
     */
    public function loadUsingCache(CategoryInterface $category, $callback)
    {
        if (in_array($callback, self::SELF_CACHED_CALLBACKS, true)) {
            $category = $this->getCategoryWithVirtualRule($category);

            return call_user_func_array([$category->getVirtualRule(), $callback], [$category]);
        }

        \Magento\Framework\Profiler::start('ES:Virtual Rule ' . $callback);
        $cacheKey = implode(
            '|',
            array_filter(
                [
                    $callback,
                    $category->getStoreId(),
                    $category->getId(),
                    $this->customerSession->getCustomerGroupId(),
                    (int) $this->config->isForceZeroResultsForDisabledCategoriesEnabled($category->getStoreId()),
                    (int) $this->config->isStoreRootUsedAsStandardCategory(),
                    $this->config->getVirtualRulesExpansionBoundariesCacheKey(),
                ],
                function ($keyPart) {
                    return $keyPart !== null;
                }
            )
        );

        $cacheData = $this->queryCache->load($cacheKey);
        $data      = ($cacheData !== false) ? $cacheData['data'] : false;

        if ($cacheData === false) {
            $category = $this->getCategoryWithVirtualRule($category);
            $data     = call_user_func_array([$category->getVirtualRule(), $callback], [$category]);
            $this->queryCache->save($cacheKey, $data, $category->getCacheTags());
        }
        \Magento\Framework\Profiler::stop('ES:Virtual Rule ' . $callback);

        return $data;
    }

    /**
     * Ensure the category virtual rule is loaded as an object, reloading the category if needed.
     *
     * @param CategoryInterface $category Category
     *
     * @return CategoryInterface
     */
    private function getCategoryWithVirtualRule(CategoryInterface $category): CategoryInterface
    {
        $virtualRule = $category->getVirtualRule();

        if (null === $virtualRule) {
            // If virtual rule is null, probably the category himself was not properly loaded.
            // So we load it through the repository and we ensure the readhandler will be called properly.
            $repository = $this->categoryRepository->create();
            $category   = $repository->get($category->getId(), $category->getStoreId());
            $category   = $this->readHandler->execute($category);
        } elseif (!is_object($virtualRule)) {
            // If virtual rule is not an object, probably the rule was not properly loaded.
            // @see https://github.com/Smile-SA/elasticsuite/issues/1985.
            // In such cases, we go through the readHandler once again.
            $category = $this->readHandler->execute($category);
        }

        return $category;
    }
}
