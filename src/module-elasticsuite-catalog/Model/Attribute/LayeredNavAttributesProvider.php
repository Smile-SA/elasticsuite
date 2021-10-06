<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Botis <botis@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Attribute;

use Smile\ElasticsuiteCatalog\Api\LayeredNavAttributeInterface;

/**
 * Layered navigation attributes provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Botis <botis@smile.fr>
 */
class LayeredNavAttributesProvider
{
    /**
     * @var LayeredNavAttributeInterface[]
     */
    protected $attributes = [];

    /**
     * LayeredNavAttributesProvider constructor.
     *
     * @param LayeredNavAttributeInterface[] $attributes Attributes.
     */
    public function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Get Layered navigation attributes list.
     *
     * @return LayeredNavAttributeInterface[]
     */
    public function getList(): array
    {
        $attributes = [];
        foreach ($this->attributes as $attribute) {
            if (!$attribute->skipAttribute()) {
                $attributes[$attribute->getAttributeCode()] = $attribute;
            }
        }

        return $attributes;
    }

    /**
     * Get layered navigation attribute.
     *
     * @param string $attributeCode Attribute code.
     *
     * @return LayeredNavAttributeInterface|null
     */
    public function getLayeredNavAttribute(string $attributeCode): ?LayeredNavAttributeInterface
    {
        return $this->getList()[$attributeCode] ?? null;
    }

    /**
     * Get layered navigation attribute by filter field.
     *
     * @param string $filterField Filter field.
     *
     * @return string|null
     */
    public function getLayeredNavAttributeByFilterField(string $filterField): ?string
    {
        foreach ($this->getList() as $attribute) {
            if ($attribute->getFilterField() === $filterField) {
                return $attribute->getAttributeCode();
            }
        }

        return null;
    }

    /**
     * Check if it is a layered navigation attribute.
     *
     * @param string $attributeCode Attribute code.
     *
     * @return bool
     */
    public function isLayeredNavAttribute(string $attributeCode): bool
    {
        return isset($this->getList()[$attributeCode]);
    }
}
