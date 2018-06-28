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
namespace Smile\ElasticsuiteCore\Model\ResourceModel\Search\Request;

/**
 * Relevance Configuration Resource model
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class RelevanceConfig extends \Magento\Config\Model\ResourceModel\Config
{
    /**
     * Save config value
     *
     * @param string $path      The config path
     * @param string $value     The config value
     * @param string $scope     The scope
     * @param string $scopeCode The scope Code
     *
     * @return $this
     */
    public function saveConfig($path, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = 'default')
    {
        return parent::saveConfig($path, $value, $scope, $scopeCode);
    }

    /**
     * Delete config value
     *
     * @param string $path      The config path
     * @param string $scope     The scope
     * @param string $scopeCode The scope Code
     *
     * @return $this
     */
    public function deleteConfig($path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = 'default')
    {
        return parent::deleteConfig($path, $scope, $scopeCode);
    }

    /**
     * Define main table.
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('smile_elasticsuite_relevance_config_data', 'config_id');
    }
}
