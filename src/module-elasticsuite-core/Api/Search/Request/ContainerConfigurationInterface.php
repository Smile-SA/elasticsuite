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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Api\Search\Request;

use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Search request container configuration interface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface ContainerConfigurationInterface
{
    /**
     * Search request container name.
     *
     * @return string
     */
    public function getName();

    /**
     * Search request container index name.
     *
     * @return string
     */
    public function getIndexName();

    /**
     * Search request container label.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Search request container mapping.
     *
     * @return MappingInterface
     */
    public function getMapping();

    /**
     * Retrieve the fulltext search relevance configuration for the container.
     *
     * @return RelevanceConfigurationInterface
     */
    public function getRelevanceConfig();

    /**
     * Current container store id.
     *
     * @return integer
     */
    public function getStoreId();

    /**
     * Retrieve filters for the container (visibility, in stock, etc ...) and the current search Context.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface[]
     */
    public function getFilters();

    /**
     * Get aggregations configured in the search container.
     *
     * @param string|QueryInterface $query        Search request query.
     * @param array                 $filters      Search request filters.
     * @param QueryInterface[]      $queryFilters Search request filters prebuilt as QueryInterface.
     *
     * @return array
     */
    public function getAggregations($query = null, $filters = [], $queryFilters = []);

    /**
     * Get the value of the track_total_hits parameter, if any.
     *
     * @return int|bool
     */
    public function getTrackTotalHits();

    /**
     * Returns if the current request is a fulltext request.
     *
     * @return bool
     */
    public function isFulltext() : bool;
}
