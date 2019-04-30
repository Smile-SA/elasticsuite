<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Modifier Interface for attributes aggregations provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface ModifierInterface
{
    /**
     * @param int                                                      $storeId      The Store ID.
     * @param string                                                   $requestName  The Request name.
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute[] $attributes   The attributes
     * @param string|QueryInterface                                    $query        Search request query.
     * @param array                                                    $filters      Search request filters.
     * @param QueryInterface[]                                         $queryFilters Search request filters prebuilt as QueryInterface.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute[]
     */
    public function modifyAttributes(
        $storeId,
        $requestName,
        $attributes,
        $query,
        $filters,
        $queryFilters
    );

    /**
     * @param int                   $storeId      The Store ID.
     * @param string                $requestName  The Request name.
     * @param array                 $aggregations The aggregations.
     * @param string|QueryInterface $query        Search request query.
     * @param array                 $filters      Search request filters.
     * @param QueryInterface[]      $queryFilters Search request filters prebuilt as QueryInterface.
     *
     * @return array
     */
    public function modifyAggregations(
        $storeId,
        $requestName,
        $aggregations,
        $query,
        $filters,
        $queryFilters
    );
}
