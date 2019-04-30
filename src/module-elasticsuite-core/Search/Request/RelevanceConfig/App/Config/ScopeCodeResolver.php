<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\RelevanceConfig\App\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ScopeResolverPool;

/**
 * Elasticsuite Relevance Configuration Scope Code Resolver.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ScopeCodeResolver
{
    /**
     * @var ScopeResolverPool
     */
    private $scopeResolverPool;

    /**
     * @var array
     */
    private $resolvedScopeCodes = [];

    /**
     * @param ScopeResolverPool $scopeResolverPool Scope Resolver Pool
     */
    public function __construct(ScopeResolverPool $scopeResolverPool)
    {
        $this->scopeResolverPool = $scopeResolverPool;
    }

    /**
     * Resolve scope code
     *
     * @param string      $scopeType Scope Type
     * @param string|null $scopeCode Scope code
     *
     * @return string
     */
    public function resolve($scopeType, $scopeCode)
    {
        if (isset($this->resolvedScopeCodes[$scopeType][$scopeCode])) {
            return $this->resolvedScopeCodes[$scopeType][$scopeCode];
        }

        $resolverScopeCode = $scopeCode;

        if (($scopeCode === null || is_numeric($scopeCode))
            && $scopeType !== ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        ) {
            $scopeResolver     = $this->scopeResolverPool->get($scopeType);
            $resolverScopeCode = $scopeResolver->getScope($scopeCode);
        }

        if ($resolverScopeCode instanceof \Magento\Framework\App\ScopeInterface) {
            $resolverScopeCode = $resolverScopeCode->getCode();
        }

        $this->resolvedScopeCodes[$scopeType][$scopeCode] = $resolverScopeCode;

        return $resolverScopeCode;
    }

    /**
     * Clean resolvedScopeCodes, store codes may have been renamed
     *
     * @return void
     */
    public function clean()
    {
        $this->resolvedScopeCodes = [];
    }
}
