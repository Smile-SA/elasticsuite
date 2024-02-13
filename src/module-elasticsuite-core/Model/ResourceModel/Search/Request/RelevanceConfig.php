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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Model\ResourceModel\Search\Request;

use \Magento\Framework\App\Config\ScopeConfigInterface;

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
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getMainTable()
        )->where(
            'path = ?',
            $path
        )->where(
            'scope = ?',
            $scope
        )->where(
            'scope_code = ?',
            $scopeCode
        );
        $row = $connection->fetchRow($select);

        $newData = ['scope' => $scope, 'scope_code' => $scopeCode, 'path' => $path, 'value' => $value];

        if ($row) {
            $whereCondition = [$this->getIdFieldName() . '=?' => $row[$this->getIdFieldName()]];
            $connection->update($this->getMainTable(), $newData, $whereCondition);

            return $this;
        }

        $connection->insert($this->getMainTable(), $newData);

        return $this;
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
        $connection = $this->getConnection();
        $connection->delete(
            $this->getMainTable(),
            [
                $connection->quoteInto('path = ?', $path),
                $connection->quoteInto('scope = ?', $scope),
                $connection->quoteInto('scope_code = ?', $scopeCode),
            ]
        );

        return $this;
    }

    /**
     * Define main table.
     *
     * @param ?\Magento\Framework\MessageQueue\PoisonPill\PoisonPillPutInterface $pillPut PillPut Interface
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return void
     */
    protected function _construct(?\Magento\Framework\MessageQueue\PoisonPill\PoisonPillPutInterface $pillPut = null)
    {
        $this->_init('smile_elasticsuite_relevance_config_data', 'config_id');
    }
}
