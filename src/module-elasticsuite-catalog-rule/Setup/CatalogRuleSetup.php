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

namespace Smile\ElasticsuiteCatalogRule\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Generic Setup for ElasticsuiteCatalogRule module.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class CatalogRuleSetup
{
    /**
     * Catalog EAV attribute table name.
     */
    private const CATALOG_EAV_ATTRIBUTE_TABLE = 'catalog_eav_attribute';

    /**
     * Catalog product attribute flag used by Magento promo rule conditions.
     */
    private const TARGET_PROMO_COLUMN = 'is_used_for_promo_rules';

    /**
     * Enable "Use for Promo Rule Conditions" for all attributes
     * matching at least one legacy ElasticSuite indexing criterion.
     *
     * SQL equivalent:
     *
     * UPDATE catalog_eav_attribute
     * SET is_used_for_promo_rules = 1
     * WHERE
     *     is_used_for_promo_rules = 0
     *     AND (
     *         is_searchable = 1
     *         OR is_visible_in_advanced_search = 1
     *         OR is_filterable > 0
     *         OR is_filterable_in_search = 1
     *         OR used_for_sort_by = 1
     *     );
     *
     * @param ModuleDataSetupInterface $setup Module setup.
     * @return void
     */
    public function migrateAttributes(ModuleDataSetupInterface $setup): void
    {
        $connection = $setup->getConnection();

        $connection->update(
            $setup->getTable(self::CATALOG_EAV_ATTRIBUTE_TABLE),
            [self::TARGET_PROMO_COLUMN => 1],
            $this->getMigrationWhereClause($connection)
        );
    }

    /**
     * Build migration WHERE clause.
     *
     * The generated clause updates only attributes that:
     * - are not already marked as promo-rule attributes
     * - match at least one of the historical ElasticSuite
     *   indexed attribute conditions.
     *
     * @param AdapterInterface $connection Database connection.
     *
     * @return string
     */
    private function getMigrationWhereClause(
        AdapterInterface $connection
    ): string {
        $legacyConditions = [
            $connection->quoteInto('is_searchable = ?', 1),
            $connection->quoteInto('is_visible_in_advanced_search = ?', 1),
            $connection->quoteInto('is_filterable > ?', 0),
            $connection->quoteInto('is_filterable_in_search = ?', 1),
            $connection->quoteInto('used_for_sort_by = ?', 1),
        ];

        return sprintf(
            '%s AND ((%s))',
            $connection->quoteInto(
                self::TARGET_PROMO_COLUMN . ' = ?',
                0
            ),
            implode(' OR ', $legacyConditions)
        );
    }
}
