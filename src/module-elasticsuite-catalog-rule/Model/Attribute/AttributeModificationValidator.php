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

namespace Smile\ElasticsuiteCatalogRule\Model\Attribute;

use Magento\Framework\Phrase;
use Smile\ElasticsuiteCatalogRule\Api\AttributeUsageCheckerInterface;

/**
 * This class defines whether an attribute can be modified or deleted
 * without breaking ElasticSuite rule integrity.
 *
 * Current protected operations:
 * - Disabling "Use for Promo Rule Conditions"
 * - Attribute deletion
 *
 * Future protected operations:
 * - Attribute option deletion
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class AttributeModificationValidator
{
    /**
     * @var AttributeUsageCheckerInterface
     */
    private AttributeUsageCheckerInterface $usageChecker;

    /**
     * Constructor.
     *
     * @param AttributeUsageCheckerInterface $usageChecker Usage checker.
     */
    public function __construct(AttributeUsageCheckerInterface $usageChecker)
    {
        $this->usageChecker = $usageChecker;
    }

    /**
     * Check if attribute can be safely removed from rule engine.
     *
     * @param string $attributeCode Attribute code.
     * @return bool
     */
    public function canBeModified(string $attributeCode): bool
    {
        return !$this->usageChecker->isAttributeUsedInRules($attributeCode);
    }

    /**
     * Generate the localized alert message explaining the restriction.
     *
     * @return Phrase
     */
    public function getBlockedMessage(): Phrase
    {
        return __(
            'This attribute is referenced by one or more ElasticSuite rule engines, ' .
            'therefore, you cannot process those modifications.'
        );
    }
}
