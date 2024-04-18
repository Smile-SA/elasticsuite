<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Catalog\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Eav;
use Magento\Catalog\Api\Data\ProductAttributeInterface;

/**
 * Plugin which is remove the "created_at" and "updated_at" attributes from the list of attribute metadata.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class HideProductAttributesPlugin
{
    /**
     * Attributes to hide.
     *
     * @var array
     */
    private $attributesToHide;

    /**
     * Constructor.
     *
     * @param array $attributesToHide Attributes to hide.
     */
    public function __construct(array $attributesToHide = [])
    {
        $this->attributesToHide = $attributesToHide;
    }

    /**
     * Hide specified attributes from the product create/edit form.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param Eav                       $subject   Object.
     * @param array                     $meta      Attribute field config.
     * @param ProductAttributeInterface $attribute Attribute.
     *
     * @return array
     */
    public function afterSetupAttributeMeta(Eav $subject, array $meta, ProductAttributeInterface $attribute): array
    {
        if (in_array($attribute->getAttributeCode(), $this->attributesToHide)) {
            $meta = [];
        }

        return $meta;
    }
}
