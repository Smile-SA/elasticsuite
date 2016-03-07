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

namespace Smile\ElasticSuiteCore\Model\Search\Relevance\Config\Structure\Element\Iterator;

/**
 * Relevance configuration Field Iterator
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Field extends \Magento\Config\Model\Config\Structure\Element\Iterator\Field
{
    /**
     * Group flyweight
     *
     * @var \Magento\Config\Model\Config\Structure\Element\Group
     */
    protected $groupFlyweight;

    /**
     * Field element flyweight
     *
     * @var \Magento\Config\Model\Config\Structure\Element\Field
     */
    protected $fieldFlyweight;

    /**
     * Class constructor
     *
     * @param \Magento\Config\Model\Config\Structure\Element\Group $groupFlyweight The Group FlyWeight
     * @param \Magento\Config\Model\Config\Structure\Element\Field $fieldFlyweight The Field FlyWeight
     */
    public function __construct(
        \Smile\ElasticSuiteCore\Model\Search\Relevance\Config\Structure\Element\Group $groupFlyweight,
        \Smile\ElasticSuiteCore\Model\Search\Relevance\Config\Structure\Element\Field $fieldFlyweight
    ) {
        $this->_groupFlyweight = $groupFlyweight;
        $this->_fieldFlyweight = $fieldFlyweight;
    }
}
