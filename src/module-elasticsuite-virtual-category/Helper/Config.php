<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Pierre Gauthier <pierre.gauthier@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Smile Elasticsuite virtual category config helper.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Pierre Gauthier <pierre.gauthier@smile.fr>
 */
class Config
{
    const XML_PATH_FORCE_ZERO_RESULTS_FOR_DISABLED_CATEGORIES = 'smile_elasticsuite_catalogsearch_settings/catalogsearch/' .
    'force_zero_results_for_disabled_categories';

    const XML_PATH_STORE_ROOT_AS_STANDARD_CATEGORY = 'smile_elasticsuite_catalogsearch_settings/catalogrule/' .
    'store_root_as_standard_category';

    const XML_PATH_RULES_EXPANSION_BOUNDARIES = 'smile_elasticsuite_catalogsearch_settings/catalogrule/' .
    'rules_boundary_categories';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig Scope configuration.
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Returns true if categories and search queries positions should be ignored for Out of Stock products.
     *
     * @param integer $storeId Store id
     *
     * @return bool
     */
    public function isForceZeroResultsForDisabledCategoriesEnabled($storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_FORCE_ZERO_RESULTS_FOR_DISABLED_CATEGORIES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns true if the store root category is used as a standard category in rules (only the products assigned to
     * the categories of the store tree), instead of being expanded with all the virtual categories of the store.
     *
     * @return bool
     */
    public function isStoreRootUsedAsStandardCategory(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_STORE_ROOT_AS_STANDARD_CATEGORY);
    }

    /**
     * Ids of the categories used as expansion boundaries: when used by another category, as its virtual root or in a
     * category condition of its rule, only their own products (and the ones of their subcategories for an anchor) are
     * used, not the ones added by their virtual subcategories.
     *
     * @return int[] Sorted category ids.
     */
    public function getVirtualRulesExpansionBoundaries(): array
    {
        $categoryIds = [];
        $value       = (string) $this->scopeConfig->getValue(self::XML_PATH_RULES_EXPANSION_BOUNDARIES);

        foreach (explode(',', $value) as $categoryId) {
            $categoryId = trim($categoryId);
            if (ctype_digit($categoryId) && (int) $categoryId > 0) {
                $categoryIds[] = (int) $categoryId;
            }
        }

        $categoryIds = array_values(array_unique($categoryIds));
        sort($categoryIds);

        return $categoryIds;
    }

    /**
     * Part of the virtual rules queries cache keys depending on the expansion boundaries, so that changing them does
     * not reuse queries built with other boundaries. Null when there is none, keeping the keys used without them.
     *
     * @return string|null
     */
    public function getVirtualRulesExpansionBoundariesCacheKey(): ?string
    {
        $categoryIds = $this->getVirtualRulesExpansionBoundaries();

        return empty($categoryIds) ? null : 'b' . sprintf('%u', crc32(implode(',', $categoryIds)));
    }
}
