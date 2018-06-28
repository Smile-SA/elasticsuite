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
     * Define main table.
     *
     * @return void
     * @codingStandardsIgnoreStart
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        //@codingStandardsIgnoreEnd
        $this->_init('smile_elasticsuite_relevance_config_data', 'config_id');
    }
}
