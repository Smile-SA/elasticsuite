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
class Config extends \Magento\Framework\App\Config
{
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
        return $this->_scopePool->getScope($scope, $scopeCode)->getValue($path);
    }
}
