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

namespace Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration;

use Magento\Framework\Config\CacheInterface;
use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration\BaseConfig\Reader;

/**
 * ElasticSuite Search requests configuration.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class BaseConfig extends \Magento\Framework\Config\Data
{
    /**
     * Cache ID for Search Request
     *
     * @var string
     */
    const CACHE_ID = 'elasticsuite_request_declaration';

    /**
     * @var IndexSettingsInterface
     */
    private $indexSettings;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Constructor.
     *
     * @param Reader                 $reader        Config file reader.
     * @param CacheInterface         $cache         Cache interface.
     * @param IndexSettingsInterface $indexSettings Index settings.
     * @param ObjectManagerInterface $objectManager Object Manager.
     * @param string                 $cacheId       Config cache id.
     */
    public function __construct(
        Reader $reader,
        CacheInterface $cache,
        IndexSettingsInterface $indexSettings,
        ObjectManagerInterface $objectManager,
        $cacheId = self::CACHE_ID
    ) {
        parent::__construct($reader, $cache, $cacheId);
        $this->indexSettings = $indexSettings;
        $this->objectManager = $objectManager;
        $this->addMappings();
        $this->addFilters();
        $this->addAggregationProviders();
        $this->addAggregationFilters();
    }

    /**
     * Append the type mapping to search requests configuration.
     *
     * @return BaseConfig
     */
    private function addMappings()
    {
        $indicesSettings = $this->indexSettings->getIndicesConfig();

        foreach ($this->_data as $requestName => $requestConfig) {
            $index = $requestConfig['index'];
            $this->_data[$requestName]['mapping'] = $indicesSettings[$index]['mapping'];
        }

        return $this;
    }

    /**
     * Append the filters to search requests configuration.
     *
     * @return BaseConfig
     */
    private function addFilters()
    {
        foreach ($this->_data as $requestName => $requestConfig) {
            if (isset($requestConfig['filters'])) {
                $filters = [];

                foreach ($requestConfig['filters'] as $filterName => $filterClass) {
                    $filters[$filterName] = $this->objectManager->get($filterClass);
                }

                $this->_data[$requestName]['filters'] = $filters;
            }
        }

        return $this;
    }

    /**
     * Append the aggregation providers to search requests configuration.
     *
     * @return BaseConfig
     */
    private function addAggregationProviders()
    {
        foreach ($this->_data as $requestName => $requestConfig) {
            if (isset($requestConfig['aggregationsProviders'])) {
                $providers = [];

                foreach ($requestConfig['aggregationsProviders'] as $providerName => $providerClass) {
                    $providers[$providerName] = $this->objectManager->create($providerClass, ['requestName' => $requestName]);
                }

                $this->_data[$requestName]['aggregationsProviders'] = $providers;
            }
        }

        return $this;
    }

    /**
     * Append the aggregation filters to search requests configuration.
     *
     * @return BaseConfig
     */
    private function addAggregationFilters()
    {
        foreach ($this->_data as $requestName => $requestConfig) {
            if (isset($requestConfig['aggregations'])) {
                $aggregations = [];

                foreach ($requestConfig['aggregations'] as $aggName => $aggConfig) {
                    $aggregations[$aggName] = $this->replaceAggregationFilter($aggConfig);
                }

                $this->_data[$requestName]['aggregations'] = $aggregations;
            }
        }

        return $this;
    }

    /**
     * Instanciates aggregation filter classes.
     *
     * @param array $aggregationConfig Aggregation config
     *
     * @return array
     */
    private function replaceAggregationFilter($aggregationConfig)
    {
        if (isset($aggregationConfig['filters'])) {
            $filters = [];

            foreach ($aggregationConfig['filters'] as $filterName => $filterClass) {
                // Done in \Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration for query filters.
                $filters[$filterName] = $this->objectManager->get($filterClass)->getFilterQuery();
            }

            $aggregationConfig['filters'] = $filters;
        }

        if (isset($aggregationConfig['childBuckets'])) {
            $childBuckets = [];
            foreach ($aggregationConfig['childBuckets'] as $aggName => $aggConfig) {
                $childBuckets[$aggName] = $this->replaceAggregationFilter($aggConfig);
            }

            $aggregationConfig['childBuckets'] = $childBuckets;
        }

        return $aggregationConfig;
    }
}
