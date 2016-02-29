<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuite________
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Model\Relevance\Config\Structure\Element\Iterator;

/**
 * _________________________________________________
 *
 * @category Smile
 * @package  Smile_ElasticSuite______________
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Field extends \Magento\Config\Model\Config\Structure\Element\Iterator\Field
{
    /**
     * Group flyweight
     *
     * @var \Magento\Config\Model\Config\Structure\Element\Group
     */
    protected $_groupFlyweight;

    /**
     * Field element flyweight
     *
     * @var \Magento\Config\Model\Config\Structure\Element\Field
     */
    protected $_fieldFlyweight;

    /**
     * @param \Magento\Config\Model\Config\Structure\Element\Group $groupFlyweight
     * @param \Magento\Config\Model\Config\Structure\Element\Field $fieldFlyweight
     */
    public function __construct(
        \Smile\ElasticSuiteCore\Model\Relevance\Config\Structure\Element\Group $groupFlyweight,
        \Smile\ElasticSuiteCore\Model\Relevance\Config\Structure\Element\Field $fieldFlyweight
    ) {
        $this->_groupFlyweight = $groupFlyweight;
        $this->_fieldFlyweight = $fieldFlyweight;
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/debug.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("CONSTRUCT");
        $e = new \Exception();
        $logger->info($e->getTraceAsString());
        $logger->info(get_class($this->_fieldFlyweight));

    }

    /**
     * Init current element
     *
     * @param array $element
     * @return void
     * @throws \LogicException
     */
    protected function _initFlyweight(array $element)
    {
        if (!isset($element[\Magento\Config\Model\Config\Structure::TYPE_KEY])) {
            throw new \LogicException('System config structure element must contain "type" attribute');
        }
        switch ($element[\Magento\Config\Model\Config\Structure::TYPE_KEY]) {
            case 'group':
                $this->_flyweight = $this->_groupFlyweight;
                break;

            case 'field':
            default:
                $this->_flyweight = $this->_fieldFlyweight;
        }
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/debug.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("INIT FLYWEIGHT");
        $logger->info(get_class($this->_flyweight));
        parent::_initFlyweight($element);
    }
}
