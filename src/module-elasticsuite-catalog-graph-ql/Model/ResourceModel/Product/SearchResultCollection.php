<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogGraphQl
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogGraphQl\Model\ResourceModel\Product;

use Magento\Framework\Api\Search\AggregationInterface;

/**
 * Custom Product Collection used for GraphQL Search requests.
 * Mostly used to inject dynamically aggregations/search results to prevent layer to reload a dummy collection after.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SearchResultCollection extends \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
{
    /**
     * @var AggregationInterface
     */
    private $aggregations;

    /**
     * Set search result aggregations and result size to the collection.
     *
     * @param AggregationInterface $aggregations Aggregations
     * @param int                  $resultSize   Search Engine results size
     */
    public function setSearchResults(AggregationInterface $aggregations, $resultSize = 0)
    {
        $this->aggregations  = $aggregations;
        $this->_totalRecords = $resultSize;
    }

    /**
     * {@inheritDoc}
     */
    public function getFacetedData($field)
    {
        if (null === $this->aggregations) {
            return parent::getFacetedData($field);
        }

        $result       = [];
        $aggregations = $this->aggregations;
        $bucket       = $aggregations->getBucket($field);

        if ($bucket) {
            foreach ($bucket->getValues() as $value) {
                $metrics = $value->getMetrics();
                $result[$value->getValue()] = $metrics;
            }
        }

        return $result;
    }
}
