<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticsuiteCatalog\Helper\IgnorePositions;

/**
 * Ignore out of stock product positions datasource.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class IgnoreOutOfStockPositionsData implements DatasourceInterface
{
    const XML_PATH_IGNORE_OOS_PRODUCT_POSITIONS = 'smile_elasticsuite_catalogsearch_settings/catalogsearch/ignore_oos_product_positions';

    /**
     * Ignore positions helper.
     *
     * @var IgnorePositions
     */
    private $ignorePositions;

    /**
     * Constructor
     *
     * @param IgnorePositions $ignorePositions Ignore positions helper.
     */
    public function __construct(
        IgnorePositions $ignorePositions
    ) {
        $this->ignorePositions = $ignorePositions;
    }

    /**
     * Remove category positions of out-of-stock products if configured so.
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData): array
    {
        if ($this->ignorePositions->isIgnoreOosPositions($storeId)) {
            foreach ($indexData as &$productData) {
                if (isset($productData['stock']['is_in_stock']) && (bool) $productData['stock']['is_in_stock'] === false) {
                    if (array_key_exists('category', $productData)) {
                        // Remove categories product position.
                        foreach ($productData['category'] as &$categoryDataRow) {
                            unset($categoryDataRow['position']);
                        }
                    }

                    if (array_key_exists('search_query', $productData)) {
                        // Remove search queries position.
                        foreach ($productData['search_query'] as &$searchQueryDataRow) {
                            unset($searchQueryDataRow['position']);
                        }
                    }
                }
            }
        }

        return $indexData;
    }
}
