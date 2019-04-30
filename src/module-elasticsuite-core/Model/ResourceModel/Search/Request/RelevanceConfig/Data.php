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

namespace Smile\ElasticsuiteCore\Model\ResourceModel\Search\Request\RelevanceConfig;

/**
 * Relevance configuration data Resource Model
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Data extends \Magento\Config\Model\ResourceModel\Config\Data
{
    /**
     * Define main table
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('smile_elasticsuite_relevance_config_data', 'config_id');
    }

    /**
     * Validate unique configuration data before save
     * Set id to object if exists configuration instead of throw exception
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @param \Magento\Framework\Model\AbstractModel $object The current configuration value being saved
     *
     * @return $this
     */
    protected function _checkUnique(\Magento\Framework\Model\AbstractModel $object)
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            [$this->getIdFieldName()]
        )->where(
            'scope = :scope'
        )->where(
            'scope_code = :scope_code'
        )->where(
            'path = :path'
        );
        $bind = [
            'scope'    => $object->getScope(),
            'scope_code' => $object->getScopeCode(),
            'path'     => $object->getPath(),
        ];

        $configId = $this->getConnection()->fetchOne($select, $bind);
        if ($configId) {
            $object->setId($configId);
        }

        return $this;
    }
}
