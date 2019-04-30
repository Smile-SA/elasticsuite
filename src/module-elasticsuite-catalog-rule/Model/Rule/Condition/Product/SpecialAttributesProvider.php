<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product;

use Smile\ElasticsuiteCatalogRule\Api\Rule\Condition\Product\SpecialAttributeInterface;

/**
 * Special Attributes Provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SpecialAttributesProvider
{
    /**
     * @var SpecialAttributeInterface[]
     */
    private $attributes = [];

    /**
     * SpecialAttributesProvider constructor.
     *
     * @param SpecialAttributeInterface[] $attributes Attributes
     */
    public function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Retrieve Special Attributes list.
     *
     * @return SpecialAttributeInterface[]
     */
    public function getList()
    {
        return $this->attributes;
    }

    /**
     * Retrieve a special attribute by code.
     *
     * @param string $attributeCode The attribute code to retrieve
     *
     * @return SpecialAttributeInterface
     */
    public function getAttribute($attributeCode)
    {
        return $this->attributes[$attributeCode];
    }
}
