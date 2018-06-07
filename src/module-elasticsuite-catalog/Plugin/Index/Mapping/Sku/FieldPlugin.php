<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Index\Mapping\Sku;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Plugin to manage proper SKU field configuration merging between elasticsuite_indices.xml and attribute configuration.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class FieldPlugin
{
    /** @var array */
    private $attributeConfig = [];

    /**
     * FieldPlugin constructor.
     *
     * @param \Smile\ElasticsuiteCatalog\Helper\ProductAttribute       $attributeHelper     Attribute Helper
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository Attribute Repository
     */
    public function __construct(
        \Smile\ElasticsuiteCatalog\Helper\ProductAttribute $attributeHelper,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository
    ) {
        // Load mapping configuration only once.
        try {
            $attribute             = $attributeRepository->get(\Magento\Catalog\Api\Data\ProductInterface::SKU);
            $this->attributeConfig = $attributeHelper->getMappingFieldOptions($attribute);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            $this->attributeConfig = [];
        }
    }

    /**
     * Retrieve SKU search weight from attribute configuration.
     *
     * @param FieldInterface $field  Field object.
     * @param integer        $result Search Weight.
     *
     * @return integer
     */
    public function afterGetSearchWeight(FieldInterface $field, $result)
    {
        if ($this->isSkuField($field)) {
            $result = $this->attributeConfig['search_weight'] ?? $result;
        }

        return $result;
    }

    /**
     * Return true if SKU attribute is used for sort by.
     *
     * @param FieldInterface $field  Field object.
     * @param integer        $result Search Weight.
     *
     * @return integer
     */
    public function afterIsUsedForSortBy(FieldInterface $field, $result)
    {
        if ($this->isSkuField($field)) {
            if (isset($this->attributeConfig['is_used_for_sort_by'])
                && ((bool) $this->attributeConfig['is_used_for_sort_by'] === true)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Return true if SKU attribute is set to be filterable.
     *
     * @param FieldInterface $field  Field object.
     * @param integer        $result Search Weight.
     *
     * @return integer
     */
    public function afterIsFilterable(FieldInterface $field, $result)
    {
        if ($this->isSkuField($field)) {
            $result = (bool) $this->attributeConfig['is_filterable'];
        }

        return $result;
    }

    /**
     * Check if current field is sku.
     *
     * @param \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface $field The field
     *
     * @return bool
     */
    private function isSkuField(FieldInterface $field)
    {
        return ($field->getName() === \Magento\Catalog\Api\Data\ProductInterface::SKU);
    }
}
