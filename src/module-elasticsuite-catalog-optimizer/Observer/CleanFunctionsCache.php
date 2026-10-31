<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteCore\Cache\Type\Elasticsuite as ElasticsuiteCache;

/**
 * Cleans the optimizer functions cache entries when an optimizer is saved or deleted.
 *
 * The functions are cached in the Elasticsuite cache type, which the optimizer model does not clean itself (it only
 * cleans the default cache, reaching them only if both use the same cache frontend).
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class CleanFunctionsCache implements ObserverInterface
{
    /**
     * @var ElasticsuiteCache
     */
    private $cache;

    /**
     * @var QueryCache
     */
    private $queryCache;

    /**
     * Constructor.
     *
     * @param ElasticsuiteCache $cache      Elasticsuite cache.
     * @param QueryCache        $queryCache Rule queries cache.
     */
    public function __construct(ElasticsuiteCache $cache, QueryCache $queryCache)
    {
        $this->cache      = $cache;
        $this->queryCache = $queryCache;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(Observer $observer)
    {
        if ($observer->getEvent()->getObject() instanceof Optimizer) {
            $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, [Optimizer::CACHE_TAG]);
            $this->queryCache->reset();
        }
    }
}
