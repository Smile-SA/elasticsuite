<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Model\Rule\Attribute;

use Magento\Framework\App\ResourceConnection;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogRule\Api\Rule\Attribute\LocationProviderInterface;

/**
 * Catalog Optimizer Attribute Location Provider.
 *
 * This provider checks whether a given attribute is used in any
 * Elasticsuite Catalog Optimizer rule.
 *
 * It performs a database lookup on the `smile_elasticsuite_optimizer` table,
 * scanning the `rule_condition` column for serialized conditions containing
 * the attribute code.
 *
 * Current implementation relies on a LIKE-based SQL query:
 *    rule_condition LIKE '%"attribute":"<attribute_code>"%'
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class OptimizerLocationProvider implements LocationProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resource;

    /**
     * Constructor.
     *
     * @param ResourceConnection $resource Database resource connection.
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Check if attribute is present in any optimizer rule.
     *
     * This method executes a COUNT query to determine if at least one
     * optimizer rule references the provided attribute.
     *
     * @param string $attribute Attribute code.
     * @return bool
     */
    public function isPresent(string $attribute): bool
    {
        if ($attribute === '') {
            return false;
        }

        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName(OptimizerInterface::TABLE_NAME);

        /**
         * Build LIKE pattern:
         * We search for exact match of JSON fragment:
         *     "attribute":"<attribute_code>"
         *
         * Important:
         * - We use bind parameter to avoid SQL injection
         * - Wildcards are added around the pattern
         */
        $likePattern = '%"attribute":"' . $attribute . '"%';

        $select = $connection->select()
            ->from($tableName, new \Zend_Db_Expr('COUNT(*)'))
            ->where('rule_condition LIKE ?', $likePattern)
            ->limit(1);

        $count = (int) $connection->fetchOne($select);

        return $count > 0;
    }
}
