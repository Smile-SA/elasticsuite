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
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

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
     *
     * @param \Magento\Framework\App\CacheInterface                   $cache              Cache.
     * @param \Magento\Customer\Model\Session                         $customerSession    Customer session.
     * @param ReadHandler                                             $readHandler        Rule read handler.
     * @param \Magento\Catalog\Api\CategoryRepositoryInterfaceFactory $categoryRepository Category factory.
     * @param Config                                                  $config             Virtual category configuration.
     */
    public function __construct(
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Customer\Model\Session $customerSession,
        ReadHandler $readHandler,
        CategoryRepositoryInterfaceFactory $categoryRepository,
        Config $config
    ) {
        $this->cache = $cache;
        $this->customerSession = $customerSession;
        $this->readHandler = $readHandler;
        $this->categoryRepository = $categoryRepository;
        $this->config = $config;
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
        \Magento\Framework\Profiler::start('ES:Virtual Rule ' . $callback);
        $cacheKey = implode(
            '|',
            [
                $callback,
                $category->getStoreId(),
                $category->getId(),
                $this->customerSession->getCustomerGroupId(),
                (int) $this->config->isForceZeroResultsForDisabledCategoriesEnabled($category->getStoreId()),
            ]
        );

        $data = $this->cache->load($cacheKey);

        // Due to the fact we serialize/unserialize completely pre-built queries as object.
        // We cannot use any implementation of SerializerInterface.
        if ($data !== false) {
            $data = unserialize($data);
        }

        if ($data === false) {
            $virtualRule = $category->getVirtualRule();

            if (null === $virtualRule) {
                // If virtual rule is null, probably the category himself was not properly loaded.
                // So we load it through the repository and we ensure the readhandler will be called properly.
                $repository  = $this->categoryRepository->create();
                $category    = $repository->get($category->getId(), $category->getStoreId());
                $category    = $this->readHandler->execute($category);
                $virtualRule = $category->getVirtualRule();
            } elseif (!is_object($virtualRule)) {
                // If virtual rule is not an object, probably the rule was not properly loaded.
                // @see https://github.com/Smile-SA/elasticsuite/issues/1985.
                // In such cases, we go through the readHandler once again.
                $category    = $this->readHandler->execute($category);
                $virtualRule = $category->getVirtualRule();
            }

            $data        = call_user_func_array([$virtualRule, $callback], [$category]);
            $cacheData   = serialize($data);
            $this->cache->save($cacheData, $cacheKey, $category->getCacheTags());
        }
        \Magento\Framework\Profiler::stop('ES:Virtual Rule ' . $callback);

        return $data;
    }
}
