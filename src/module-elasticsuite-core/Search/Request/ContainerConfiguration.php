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

namespace Smile\ElasticsuiteCore\Search\Request;

use Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration\BaseConfig;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexInterface;
use Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration\RelevanceConfig\Factory as RelevanceConfigFactory;
use Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfigurationInterface;

/**
 * Search request container configuration implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ContainerConfiguration implements ContainerConfigurationInterface
{
    /**
     * @var string
     */
    private $containerName;

    /**
     * @var integer
     */
    private $storeId;

    /**
     * @var BaseConfig
     */
    private $baseConfig;

    /**
     * @var IndexOperationInterface
     */
    private $indexManager;

    /**
     * @var RelevanceConfigurationInterface
     */
    private $relevanceConfig;

    /**
     * Constructor.
     *
     * @param string                  $containerName          Search request container name.
     * @param integer                 $storeId                Store id.
     * @param BaseConfig              $baseConfig             XML file configuration.
     * @param RelevanceConfigFactory  $relevanceConfigFactory Fulltext search relevance factory
     * @param IndexOperationInterface $indexManager           Index manager (used to load mappings).
     */
    public function __construct(
        $containerName,
        $storeId,
        BaseConfig $baseConfig,
        RelevanceConfigFactory $relevanceConfigFactory,
        IndexOperationInterface $indexManager
    ) {
        $this->containerName   = $containerName;
        $this->storeId         = $storeId;
        $this->baseConfig      = $baseConfig;
        $this->indexManager    = $indexManager;
        $this->relevanceConfig = $relevanceConfigFactory->create($storeId, $containerName);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->containerName;
    }

    /**
     * {@inheritDoc}
     */
    public function getIndexName()
    {
        return $this->getIndex()->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return $this->readBaseConfigParam('label');
    }

    /**
     * {@inheritDoc}
     */
    public function getMapping()
    {
        return $this->getIndex()->getMapping();
    }

    /**
     * {@inheritDoc}
     */
    public function getRelevanceConfig()
    {
        return $this->relevanceConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        $filters = [];

        /** @var \Smile\ElasticsuiteCore\Api\Search\Request\Container\FilterInterface $filter */
        foreach ($this->readBaseConfigParam('filters', []) as $filter) {
            // Not using the filter name as array key, to prevent collision with filters added via addFieldToFilter.
            $filters[] = $filter->getFilterQuery();
        }

        return array_filter($filters);
    }

    /**
     * {@inheritDoc}
     */
    public function getAggregations($query = null, $filters = [], $queryFilters = [])
    {
        $aggregations = $this->readBaseConfigParam('aggregations', []);

        /** @var \Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfiguration\AggregationProviderInterface $provider */
        foreach ($this->readBaseConfigParam('aggregationsProviders', []) as $provider) {
            $aggregations = array_merge(
                $aggregations,
                $provider->getAggregations($this->getStoreId(), $query, $filters, $queryFilters)
            );
        }

        return $aggregations;
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackTotalHits()
    {
        return $this->readBaseConfigParam('track_total_hits');
    }

    /**
     * {@inheritDoc}
     */
    public function isFulltext() : bool
    {
        return (bool) filter_var($this->readBaseConfigParam('fulltext'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /**
     * Read configuration param from base config.
     *
     * @param string $param   Param name.
     * @param mixed  $default Default value if not set.
      *
     * @return mixed
     */
    private function readBaseConfigParam($param, $default = null)
    {
        return $this->baseConfig->get($this->containerName . '/' . $param) ?? $default;
    }

    /**
     * Retrieve the index associated with the currrent search request container.
     *
     * @return IndexInterface
     */
    private function getIndex()
    {
        $indexName = $this->readBaseConfigParam('index');

        return $this->indexManager->getIndexByName($indexName, $this->storeId);
    }
}
