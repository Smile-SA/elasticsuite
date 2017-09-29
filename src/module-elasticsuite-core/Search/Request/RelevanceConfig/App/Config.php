<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Search\Request\RelevanceConfig\App;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Relevance configuration implementation.
 *
 * @category Smile
 * @package  Smile\ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Config implements ScopeConfigInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\RelevanceConfig\App\Config\ScopePool
     */
    private $scopePool;

    /**
     * Config constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\RelevanceConfig\App\Config\ScopePool $scopePool Scope Pool
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\RelevanceConfig\App\Config\ScopePool $scopePool
    ) {
        $this->scopePool = $scopePool;
    }

    /**
     * Retrieve config value by path and scope
     *
     * @param string      $path      The path to retrieve config for
     * @param string      $scope     The scope
     * @param null|string $scopeCode The scope code, if any
     *
     * @return mixed
     */
    public function getValue(
        $path = null,
        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ) {
        return $this->scopePool->getScope($scope, $scopeCode)->getValue($path);
    }

    /**
     * Retrieve config flag by path and scope
     *
     * @param string      $path      The path through the tree of configuration values, e.g., 'general/store_information/name'
     * @param string      $scopeType The scope to use to determine config value, e.g., 'store' or 'default'
     * @param null|string $scopeCode The scope code, if any
     *
     * @return bool
     */
    public function isSetFlag($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        return !!$this->getValue($path, $scopeType, $scopeCode);
    }
}
