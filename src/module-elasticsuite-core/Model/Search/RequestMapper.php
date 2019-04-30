<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Search;

use \Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use \Magento\Framework\Api\Search\SearchCriteriaInterface;

/**
 * ElasticSuite search API implementation : convert search criteria to search request.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class RequestMapper
{
    /**
     * Extract sort orders from the search criteria.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container config.
     * @param SearchCriteriaInterface         $searchCriteria         Search criteria.
     *
     * @return array
     */
    public function getSortOrders(ContainerConfigurationInterface $containerConfiguration, SearchCriteriaInterface $searchCriteria)
    {
        $sortOrders = [];

        foreach ($searchCriteria->getSortOrders() ?? [] as $sortOrder) {
            $sortOrders[$sortOrder->getField()] = ['direction' => $sortOrder->getDirection()];
        }

        return $sortOrders;
    }

    /**
     * Extract filters from the search criteria.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container config.
     * @param SearchCriteriaInterface         $searchCriteria         Search criteria.
     *
     * @return array
     */
    public function getFilters(ContainerConfigurationInterface $containerConfiguration, SearchCriteriaInterface $searchCriteria)
    {
        $filters = [];

        foreach ($searchCriteria->getFilterGroups() ?? [] as $filterGroup) {
            foreach ($filterGroup->getFilters() ?? [] as $filter) {
                if ($filter->getField() != "search_term") {
                    $filters[$filter->getField()][$filter->getConditionType()] = $filter->getValue();
                }
            }
        }

        return $filters;
    }
}
