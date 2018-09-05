<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
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
 * @category Smile_Elasticsuite
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
    public function getTypeName()
    {
        return $this->readBaseConfigParam('type');
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
        $typeName = $this->getTypeName();
        $type     = $this->getIndex()->getType($typeName);

        return $type->getMapping();
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
    public function getFilters(\Smile\ElasticsuiteCore\Api\Search\ContextInterface $searchContext)
    {
        $filters = [];

        /** @var \Smile\ElasticsuiteCore\Api\Search\Request\Container\FilterInterface $filter */
        foreach ($this->readBaseConfigParam('filters') as $filter) {
            // Not using the filter name as array key, to prevent collision with filters added via addFieldToFilter.
            $filters[] = $filter->getFilterQuery($searchContext);
        }

        return array_filter($filters);
    }

    /**
     * {@inheritDoc}
     */
    public function getAggregations(\Smile\ElasticsuiteCore\Api\Search\ContextInterface $searchContext)
    {
        return $this->readBaseConfigParam('aggregations');
    }

    /**
     * Read configuration param from base config.
     *
     * @param string $param Param name.
     *
     * @return mixed
     */
    private function readBaseConfigParam($param)
    {
        return $this->baseConfig->get($this->containerName . '/' . $param);
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
