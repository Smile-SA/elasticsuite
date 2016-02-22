<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Block\Adminhtml\Search\Request\RelevanceConfig\Form\Field;

/**
 * Configuration field factory
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Factory extends \Magento\Config\Block\System\Config\Form\Field\Factory
{
    /**
     * Create new config field object
     *
     * @param array $data The object data
     *
     * @return \Magento\Config\Block\System\Config\Form\Field
     */
    public function create(array $data = [])
    {
        return $this->_objectManager
            ->create('Smile\ElasticSuiteCore\Block\Adminhtml\Search\Request\RelevanceConfig\Form\Field', $data);
    }
}
