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

namespace Smile\ElasticSuiteCore\Model\Search\Request\RelevanceConfig\Structure\Element\Iterator;

use Smile\ElasticSuiteCore\Model\Search\Request\RelevanceConfig\Structure\Element\Field as ConfigField;
use Smile\ElasticSuiteCore\Model\Search\Request\RelevanceConfig\Structure\Element\Group;

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
     * Class constructor
     *
     * @param Group       $groupFlyweight The Group FlyWeight
     * @param ConfigField $fieldFlyweight The Field FlyWeight
     */
    public function __construct(
        Group $groupFlyweight,
        ConfigField $fieldFlyweight
    ) {
        $this->_groupFlyweight = $groupFlyweight;
        $this->_fieldFlyweight = $fieldFlyweight;
    }
}
