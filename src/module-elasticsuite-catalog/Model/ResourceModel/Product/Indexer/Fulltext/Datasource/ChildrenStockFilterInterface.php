<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Magento\Framework\DB\Select;

/**
 * Interface ChildrenStockFilterInterface
 *
 * Allows to exclude non salable children products from the data indexed for their parents.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 */
interface ChildrenStockFilterInterface
{
    /**
     * Exclude non salable children from a children select when the catalog is configured
     * to hide out of stock products. The select is returned untouched otherwise.
     *
     * @param Select $select     Children select.
     * @param string $childAlias Alias of the child product entity table in the select.
     * @param int    $storeId    Store id.
     *
     * @return Select
     */
    public function addSalableFilter(Select $select, string $childAlias, int $storeId): Select;
}
