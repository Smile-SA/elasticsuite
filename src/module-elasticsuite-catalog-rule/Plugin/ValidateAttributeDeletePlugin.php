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
 * Prevent deletion of product attributes is currently referenced
 * by ElasticSuite rule engines.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class ValidateAttributeDeletePlugin
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
     * Validate attribute deletion to prevent breaking rules.
     *
     * @param AttributeModel $subject Attribute model.
     * @return array|null
     * @throws LocalizedException
     */
    public function beforeDelete(AttributeModel $subject): ?array
    {
        $attributeCode = (string) $subject->getAttributeCode();

        if (!$this->validator->canBeModified($attributeCode)) {
            throw new LocalizedException($this->validator->getBlockedMessage());
        }

        return null;
    }
}
