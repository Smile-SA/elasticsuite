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
namespace Smile\ElasticsuiteCore\Search\Request\SortOrder;

use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\SortOrder\DefaultSortOrderProviderInterface;
use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;

/**
 * Default Sort Order Provider implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class DefaultSortOrderProvider implements DefaultSortOrderProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDefaultSortOrders($orders, MappingInterface $mapping)
    {
        $defaultOrders = [
            SortOrderInterface::DEFAULT_SORT_FIELD => SortOrderInterface::SORT_DESC,
            $mapping->getIdField()->getName()      => SortOrderInterface::SORT_DESC,
        ];

        if (count($orders) > 0) {
            $firstOrder = current($orders);
            if ($firstOrder['direction'] == SortOrderInterface::SORT_DESC) {
                $defaultOrders[SortOrderInterface::DEFAULT_SORT_FIELD] = SortOrderInterface::SORT_ASC;
                $defaultOrders[$mapping->getIdField()->getName()]      = SortOrderInterface::SORT_ASC;
            }
        }

        return $defaultOrders;
    }
}
