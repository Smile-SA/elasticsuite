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
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Map of flyweight types
     *
     * @var array
     */
    protected $flyweightMap = [
        'section' => 'Smile\ElasticSuiteCore\Model\Relevance\Config\Structure\Element\Section',
        'group'   => 'Smile\ElasticSuiteCore\Model\Relevance\Config\Structure\Element\Group',
        'field'   => 'Smile\ElasticSuiteCore\Model\Relevance\Config\Structure\Element\Field',
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager The object manager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create element flyweight flyweight
     *
     * @param string $type The element type
     *
     * @return \Magento\Config\Model\Config\Structure\ElementInterface
     */
    public function create($type)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/debug.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("RORUA FACTORY! CREATING {$type}");
        return $this->objectManager->create($this->flyweightMap[$type]);
    }
}
