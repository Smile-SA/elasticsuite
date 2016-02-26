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
namespace Smile\ElasticSuiteCore\Model\Relevance\Config\Structure\Element;

use Smile\ElasticSuiteCore\Api\Config\Relevance\FieldInterface;

/**
 * Custom field element to manager container scope visibility
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Field extends \Magento\Config\Model\Config\Structure\Element\Field implements FieldInterface
{
    /**
     * Check whether field should be shown in website scope
     *
     * @return bool
     */
    public function showInContainer()
    {
        return isset($this->_data['showInContainer']) && (int) $this->_data['showInContainer'];
    }
}
