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

use Smile\ElasticsuiteCatalog\Api\SpecialAttributeInterface;

/**
 * Special Attributes Provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Botis <botis@smile.fr>
 */
class SpecialAttributesProvider
{
    /**
     * @var SpecialAttributeInterface[]
     */
    protected $attributes = [];

    /**
     * SpecialAttributesProvider constructor.
     *
     * @param SpecialAttributeInterface[] $attributes Attributes.
     */
    public function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Get special attributes list.
     *
     * @return SpecialAttributeInterface[]
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
     * Get special attribute.
     *
     * @param string $attributeCode Attribute code.
     *
     * @return SpecialAttributeInterface|null
     */
    public function getSpecialAttribute(string $attributeCode): ?SpecialAttributeInterface
    {
        return $this->getList()[$attributeCode] ?? null;
    }

    /**
     * Get special attribute.
     *
     * @param string $filterField Filter field.
     *
     * @return string|null
     */
    public function getSpecialAttributeByFilterField(string $filterField): ?string
    {
        foreach ($this->getList() as $attribute) {
            if ($attribute->getFilterField() === $filterField) {
                return $attribute->getAttributeCode();
            }
        }

        return null;
    }

    /**
     * Check if attribute is special.
     *
     * @param string $attributeCode Attribute code.
     *
     * @return bool
     */
    public function isSpecialAttribute(string $attributeCode): bool
    {
        return isset($this->getList()[$attributeCode]);
    }
}
