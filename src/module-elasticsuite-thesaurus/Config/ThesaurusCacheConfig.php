<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteThesaurus\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Thesaurus cache configuration helper.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class ThesaurusCacheConfig
{
    /** @var string */
    const ALWAYS_CACHE_RESULTS_XML_PATH = 'smile_elasticsuite_thesaurus_settings/cache/always';

    /** @var string */
    const MIN_REWRITES_FOR_CACHING_XML_PATH = 'smile_elasticsuite_thesaurus_settings/cache/min_rewites';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig Scope config interface.
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Returns true if it is allowed by the config to store in cache the results of the thesaurus rules computation.
     *
     * @param ContainerConfigurationInterface $config        Container configuration.
     * @param int                             $rewritesCount Number of rewrites/alternative queries.
     *
     * @return bool
     */
    public function isCacheStorageAllowed(ContainerConfigurationInterface $config, $rewritesCount)
    {
        $alwaysCache = $this->scopeConfig->isSetFlag(
            self::ALWAYS_CACHE_RESULTS_XML_PATH,
            ScopeInterface::SCOPE_STORES,
            $config->getStoreId()
        );

        if (false === $alwaysCache) {
            $minRewritesForCaching = $this->scopeConfig->getValue(
                self::MIN_REWRITES_FOR_CACHING_XML_PATH,
                ScopeInterface::SCOPE_STORES,
                $config->getStoreId()
            );

            return ($rewritesCount >= $minRewritesForCaching);
        }

        return true;
    }
}
