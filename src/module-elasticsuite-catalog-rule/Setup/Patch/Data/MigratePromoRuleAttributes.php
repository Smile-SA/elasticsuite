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

namespace Smile\ElasticsuiteCatalogRule\Setup\Patch\Data;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Migrate legacy ElasticSuite rule attributes to the dedicated
 * "Use for Promo Rule Conditions" flag.
 *
 * Historically, ElasticSuite rule engines (Virtual Categories, Optimizers, etc.)
 * relied on attributes that were part of the search index.
 * These attributes were identified using the same conditions as those defined
 * in: {@see \Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Fulltext\Datasource\AbstractAttributeData}
 *
 * Since rule engines will now exclusively rely on the "is_used_for_promo_rules" attribute flag,
 * this migration ensures that existing installations retain access to all attributes that
 * were previously available in rule conditions.
 *
 * The patch forcefully enables "is_used_for_promo_rules" for all product attributes
 * matching at least one of the historical indexing criteria.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class MigratePromoRuleAttributes implements DataPatchInterface
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
     * Setup instance.
     *
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * Constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup Module setup instance.
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Apply patch.
     *
     * @return $this
     */
    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();

        try {
            $this->migrateAttributes(
                $this->moduleDataSetup->getConnection()
            );
        } finally {
            $this->moduleDataSetup->endSetup();
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getAliases(): array
    {
        return [];
    }

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
     * @param AdapterInterface $connection Database connection.
     *
     * @return void
     */
    private function migrateAttributes(AdapterInterface $connection): void
    {
        $connection->update(
            $this->moduleDataSetup->getTable(self::CATALOG_EAV_ATTRIBUTE_TABLE),
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
