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
namespace Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig;

/**
 * Relevance Configuration Field implementation
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Field extends \Magento\Config\Model\Config\Structure\Element\Field implements FieldInterface
{
    /**
     * Check whether field should be shown in container scope
     *
     * @return bool
     */
    public function showInContainer()
    {
        return isset($this->_data['showInContainer']) && (int) $this->_data['showInContainer'];
    }
}
