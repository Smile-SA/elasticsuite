<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteCatalogRule\Plugin;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as AttributeModel;
use Magento\Framework\Exception\LocalizedException;
use Smile\ElasticsuiteCatalogRule\Model\Attribute\AttributeModificationValidator;

/**
 * Prevent modification of the "Use for Promo Rule Conditions" flag when
 * the attribute is currently referenced by ElasticSuite rule engines.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class ValidatePromoRuleFlagPlugin
{
    /**
     * @var AttributeModificationValidator
     */
    private AttributeModificationValidator $validator;

    /**
     * Constructor.
     *
     * @param AttributeModificationValidator $validator Attribute modification validator.
     */
    public function __construct(AttributeModificationValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Validate promo rule flag changes before attribute save.
     *
     * @param AttributeModel $subject Attribute model.
     * @return array|null
     * @throws LocalizedException
     */
    public function beforeSave(AttributeModel $subject): ?array
    {
        // Safe to pass if it's a completely new attribute.
        if (!$subject->getId()) {
            return null;
        }

        // Check if the native promo flag is transitioning from Yes (1) -> No (0).
        if ($subject->hasDataChanges() && (int) $subject->getOrigData('is_used_for_promo_rules') === 1) {
            if ((int) $subject->getData('is_used_for_promo_rules') === 0) {
                $attributeCode = (string) $subject->getAttributeCode();

                if (!$this->validator->canBeModified($attributeCode)) {
                    throw new LocalizedException($this->validator->getBlockedMessage());
                }
            }
        }

        return null;
    }
}
