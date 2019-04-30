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

namespace Smile\ElasticsuiteCore\Model\ResourceModel\Search\Request\RelevanceConfig\Data;

/**
 * Relevance configuration collection
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Collection extends \Magento\Config\Model\ResourceModel\Config\Data\Collection
{
    /**
     * Add scope filter to collection
     *
     * @param string $scope     The scope
     * @param string $scopeCode The scope code
     * @param string $section   The section
     *
     * @return Collection
     */
    public function addScopeFilter($scope, $scopeCode, $section)
    {
        $this->addFieldToFilter('scope', $scope);
        $this->addFieldToFilter('scope_code', trim($scopeCode));
        $this->addFieldToFilter('path', ['like' => $section . '/%']);

        return $this;
    }

    /**
     * Define resource model
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Value',
            'Smile\ElasticsuiteCore\Model\ResourceModel\Search\Request\RelevanceConfig\Data'
        );
    }
}
