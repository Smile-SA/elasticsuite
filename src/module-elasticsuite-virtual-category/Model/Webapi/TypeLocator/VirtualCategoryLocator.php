<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\Webapi\TypeLocator;

use Magento\Catalog\Model\Category;
use Magento\Framework\Webapi\CustomAttributeTypeLocatorInterface;
use Smile\ElasticsuiteVirtualCategory\Api\Data\VirtualRuleInterface;

/**
 * Custom Locator for virtual_rule attribute.
 *
 * Because it has no backend model (to allow ElasticSuite uninstall process), it's resolved as "string" which is wrong.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class VirtualCategoryLocator extends \Magento\Eav\Model\TypeLocator\ComplexType implements CustomAttributeTypeLocatorInterface
{
    /**
     * {@inheritDoc}
     */
    public function getType($attributeCode, $entityType)
    {
        if ($entityType === Category::ENTITY && $attributeCode === 'virtual_rule') {
            return VirtualRuleInterface::class;
        }

        return parent::getType($attributeCode, $entityType);
    }

    /**
     * {@inheritDoc}
     */
    public function getAllServiceDataInterfaces()
    {
        return [VirtualRuleInterface::class];
    }
}
