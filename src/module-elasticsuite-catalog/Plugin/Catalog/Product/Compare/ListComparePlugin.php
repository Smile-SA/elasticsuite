<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Edward Crocombe <ecrocombe@outlook.com>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Catalog\Product\View;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Smile\ElasticsuiteCatalog\Helper\ProductAttribute;

/**
 * Catalog Product List Compare plugin.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Edward Crocombe <ecrocombe@outlook.com>
 */
class ListComparePlugin
{
    /**
     * @var null|\Magento\Catalog\Api\Data\ProductAttributeInterface[]
     */
    private $attributes;

    /**
     * @var \Smile\ElasticsuiteCatalog\Helper\ProductAttribute
     */
    private $productAttributeHelper;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepository
     */
    private $productAttributeRepository;

    /**
     * Constructor.
     *
     * @param ProductAttribute                    $productAttributeHelper     ElasticSuite product attributes helper.
     * @param ProductAttributeRepositoryInterface $productAttributeRepository Formats numbers to a locale
     */
    public function __construct(
        ProductAttribute $productAttributeHelper,
        ProductAttributeRepositoryInterface $productAttributeRepository
    ) {
        $this->productAttributeHelper = $productAttributeHelper;
        $this->productAttributeRepository = $productAttributeRepository;
    }

    /**
     * Add display pattern for frontend display
     *
     * @param \Magento\Catalog\Block\Product\Compare\ListCompare $subject   Plugin Subject
     * @param \Magento\Framework\Phrase|string                   $result    Plugin Result
     * @param \Magento\Catalog\Model\Product                     $product   Product
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute Product Attribute
     *
     * @return \Magento\Framework\Phrase|string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetProductAttributeValue(
        \Magento\Catalog\Block\Product\Compare\ListCompare $subject,
        $result,
        $product,
        $attribute
    ) {
        if (!$this->productAttributeHelper->isFrontendProductDisplayPatternEnabled()) {
            return $result;
        }

        $value = $attribute->getFrontend()->getValue($product);
        if (is_numeric($value) && strlen($attribute->getData('display_pattern') ?? '') > 0) {
            $result = $this->productAttributeHelper->formatProductAttributeValueDisplayPattern($attribute, $value);
        }

        return $result;
    }

    /**
     * Retrieve Product Compare Attributes
     *
     * Default getAttributes retrieves columns from eav_attribute table only,
     *  both the display_pattern and display_precision values are on the catalog_eav_attribute table.
     *
     * @param \Magento\Catalog\Block\Product\Compare\ListCompare      $subject Plugin Subject
     * @param \Magento\Eav\Model\Entity\Attribute\AbstractAttribute[] $result  Plugin Result
     *
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAttributes(\Magento\Catalog\Block\Product\Compare\ListCompare $subject, $result)
    {
        if (!$this->productAttributeHelper->isFrontendProductDisplayPatternEnabled()) {
            return $result;
        }

        if ($this->attributes === null) {
            $this->attributes = [];
            foreach (array_keys($result) as $attributeCode) {
                $this->attributes[$attributeCode] = $this->productAttributeRepository->get($attributeCode);
            }
        }

        return $this->attributes;
    }
}
