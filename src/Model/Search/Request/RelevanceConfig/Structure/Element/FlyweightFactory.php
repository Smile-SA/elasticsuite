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

namespace Smile\ElasticSuiteCore\Model\Search\Request\RelevanceConfig\Structure\Element;

/**
 * Custom Flyweight factory to instantiate custom field element
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class FlyweightFactory extends \Magento\Config\Model\Config\Structure\Element\FlyweightFactory
{
    /**
     * Map of flyweight types
     *
     * @var array
     */
    protected $flyweightMap = [
        'section' => 'Smile\ElasticSuiteCore\Model\Search\Request\RelevanceConfig\Structure\Element\Section',
        'group'   => 'Smile\ElasticSuiteCore\Model\Search\Request\RelevanceConfig\Structure\Element\Group',
        'field'   => 'Smile\ElasticSuiteCore\Model\Search\Request\RelevanceConfig\Structure\Element\Field',
    ];

    /**
     * Create element flyweight flyweight
     *
     * @param string $type The element type
     *
     * @return \Magento\Config\Model\Config\Structure\ElementInterface
     */
    public function create($type)
    {
        return $this->_objectManager->create($this->flyweightMap[$type]);
    }
}
