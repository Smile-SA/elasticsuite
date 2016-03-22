<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCatalog\Model\Layer\Filter\Item;

/**
 * Filter item factory for multiselect attributes.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AttributeFactory extends \Magento\Catalog\Model\Layer\Filter\ItemFactory
{
    /**
     * Constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager Object manager.
     * @param string                                    $instanceName  Name of the class to instantiated.
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = 'Smile\ElasticSuiteCatalog\Model\Layer\Filter\Item\Attribute'
    ) {
        parent::__construct($objectManager, $instanceName);
    }
}
