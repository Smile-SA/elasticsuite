<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Model\ResourceModel\Relevance\Config\Data;

/**
 * Relevance configuration collection
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Collection extends \Magento\Config\Model\ResourceModel\Config\Data\Collection
{
    /**
     * Define resource model
     *
     * @return void
     */
    // @codingStandardsIgnoreStart This method is inherited
    protected function _construct()
    {
        // @codingStandardIgnoreEnd
        $this->_init(
            'Smile\ElasticSuiteCore\Model\Relevance\Config\Value',
            'Smile\ElasticSuiteCore\Model\ResourceModel\Relevance\Config\Data'
        );
    }
}
