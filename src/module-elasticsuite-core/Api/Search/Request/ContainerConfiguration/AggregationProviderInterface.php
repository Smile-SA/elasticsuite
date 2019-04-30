<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfiguration;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Aggregations Provider interface for search requests.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface AggregationProviderInterface
{
    /**
     * Returns aggregations configured in the search container, and according to currently applied query and filters.
     *
     * @param int                   $storeId      The Store ID.
     * @param string|QueryInterface $query        Search request query.
     * @param array                 $filters      Search request filters.
     * @param QueryInterface[]      $queryFilters Search request filters prebuilt as QueryInterface.
     *
     * @return array
     */
    public function getAggregations(
        $storeId,
        $query = null,
        $filters = [],
        $queryFilters = []
    );
}
