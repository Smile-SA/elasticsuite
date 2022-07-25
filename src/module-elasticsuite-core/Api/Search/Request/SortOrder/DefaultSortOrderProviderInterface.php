<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2022 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Api\Search\Request\SortOrder;

use Smile\ElasticsuiteCore\Api\Index\MappingInterface;

/**
 * Default Sort Order Provider Interface
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
interface DefaultSortOrderProviderInterface
{
    /**
     * Get default sort orders
     *
     * @param array            $orders  Original orders.
     * @param MappingInterface $mapping Mapping.
     *
     * @return array
     */
    public function getDefaultSortOrders($orders, MappingInterface $mapping);
}
