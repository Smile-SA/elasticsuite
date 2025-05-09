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

namespace Smile\ElasticsuiteCore\Index\Indices;

use Smile\ElasticsuiteCore\Index\Indices\Config\Reader;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;
use Smile\ElasticsuiteCore\Api\Index\TypeInterfaceFactory as TypeFactory;
use Smile\ElasticsuiteCore\Api\Index\MappingInterfaceFactory as MappingFactory;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterfaceFactory as MappingFieldFactory;
use Smile\ElasticsuiteCore\Api\Index\DataSourceResolverInterfaceFactory as DataSourceResolverFactory;

/**
 * ElasticSuite indices configuration;
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Config extends \Magento\Framework\Config\Data
{
    /**
     * Cache ID for indices configuration.
     *
     * @var string
     */
    const CACHE_ID = 'indices_config';

    /**
     * Factory used to build mappings.
     *
     * @var \Smile\ElasticsuiteCore\Api\Index\MappingInterfaceFactory
     */
    private $mappingFactory;

    /**
     * Factory used to build mapping fields.
     *
     * @var \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterfaceFactory
     */
    private $mappingFieldFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\DataSourceResolverInterfaceFactory
     */
    private $dataSourceResolverFactory;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\Framework\Config\CacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $cacheId;

    /**
     * Instantiate config.
     *
     * @param Reader                    $reader                    Config file reader.
     * @param CacheInterface            $cache                     Cache instance.
     * @param MappingFactory            $mappingFactory            Index mapping factory.
     * @param MappingFieldFactory       $mappingFieldFactory       Index mapping field factory.
     * @param DataSourceResolverFactory $dataSourceResolverFactory Data Source Resolver Factory.
     * @param SerializerInterface       $serializer                Serializer.
     * @param string                    $cacheId                   Default config cache id.
     */
    public function __construct(
        Reader $reader,
        CacheInterface $cache,
        MappingFactory $mappingFactory,
        MappingFieldFactory $mappingFieldFactory,
        DataSourceResolverFactory $dataSourceResolverFactory,
        SerializerInterface $serializer,
        $cacheId = self::CACHE_ID
    ) {
        $this->mappingFactory            = $mappingFactory;
        $this->mappingFieldFactory       = $mappingFieldFactory;
        $this->dataSourceResolverFactory = $dataSourceResolverFactory;
        $this->serializer                = $serializer;
        $this->cache                     = $cache;
        $this->cacheId                   = $cacheId;
        $this->cacheTags[]               = $cacheId;
        $this->cacheTags[]               = \Magento\Framework\App\Cache\Type\Config::CACHE_TAG;
        $this->cacheTags[]               = \Smile\ElasticsuiteCore\Cache\Type\Elasticsuite::CACHE_TAG;

        parent::__construct($reader, $cache, $cacheId, $serializer);
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        parent::reset();
        $this->cache->clean(
            \Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            [
                $this->cacheId,
                \Smile\ElasticsuiteCore\Cache\Type\Elasticsuite::CACHE_TAG,
            ]
        );
        $this->initData();
    }

    /**
     * Init data for configuration.
     *
     * @return void
     */
    protected function initData()
    {
        parent::initData();
        array_walk($this->_data, function (&$indexConfigData, $indexName) {
            $indexConfigData = $this->initIndexConfig($indexName, $indexConfigData);
        });
    }

    /**
     * Init type, mapping, and fields from a index configuration array.
     *
     * @param string $indexName       Processed index name.
     * @param array  $indexConfigData Processed index configuration.
     *
     * @return array
     */
    private function initIndexConfig(string $indexName, array $indexConfigData)
    {
        $types = [];

        foreach ($indexConfigData['types'] as $typeConfigData) {
            $fields  = $this->getMappingFields($indexName, $typeConfigData);
            $mapping = $this->mappingFactory->create(
                ['idFieldName' => $typeConfigData['idFieldName'], 'fields' => $fields]
            );
        }

        if (count($types) > 1) {
            throw new \LogicException("Can not add several types in the same index.");
        }

        $defaultSearchType = $indexConfigData['defaultSearchType'];

        return [
            'mapping'           => $mapping,
            'types'             => $types,
            'defaultSearchType' => $defaultSearchType,
            'datasources'       => $typeConfigData['datasources'] ?? [], // @deprecated.
        ];
    }

    /**
     * Prepare mapping fields by merging static fields with dynamic ones.
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param string $indexName      Index Name.
     * @param array  $typeConfigData Processed type configuration.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface[]
     */
    private function getMappingFields($indexName, $typeConfigData)
    {
        \Magento\Framework\Profiler::start('ES:Get dynamic fields config');
        $fields = $this->getDynamicFields($indexName);
        \Magento\Framework\Profiler::stop('ES:Get dynamic fields config');

        foreach ($typeConfigData['mapping']['staticFields'] as $fieldName => $fieldConfig) {
            $field = $this->mappingFieldFactory->create(['name' => $fieldName] + $fieldConfig);

            if (isset($fields[$fieldName])) {
                // Field also exists with dynamic providers.
                // We merge the dynamic config and the config coming from configuration file.
                // XML file has precedence.
                $config = $fieldConfig['fieldConfig'] ?? [] + ['type' => $fieldConfig['type'] ?? null];
                $field  = $fields[$fieldName]->mergeConfig($config);
            }

            $fields[$fieldName] = $field;
        }

        return $fields;
    }

    /**
     * Get dynamic fields.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param string $indexName Index Name.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface[]
     */
    private function getDynamicFields($indexName)
    {
        $fields       = [];
        $cacheId      = implode('|', [$this->cacheId, $indexName]);
        $fieldsConfig = $this->cache->load($cacheId);

        if (false === $fieldsConfig) {
            $resolver    = $this->dataSourceResolverFactory->create();
            $dataSources = $resolver->getDataSources($indexName);

            /** @var DynamicFieldProviderInterface[] $dynamicFieldProviders */
            $dynamicFieldProviders = array_filter($dataSources, [$this, 'isDynamicFieldsProvider']);

            foreach ($dynamicFieldProviders as $dynamicFieldProvider) {
                $fields += $dynamicFieldProvider->getFields();
            }

            $fieldsConfig = [];
            foreach ($fields as $fieldName => $field) {
                $fieldsConfig[$fieldName] = [
                    'type'        => $field->getType(),
                    'nestedPath'  => $field->getNestedPath(),
                    'fieldConfig' => $field->getConfig(),
                ];
            }

            $this->cache->save($this->serializer->serialize($fieldsConfig), $cacheId, $this->cacheTags);
        } else {
            $fieldsConfig = $this->serializer->unserialize($fieldsConfig);
        }

        foreach ($fieldsConfig as $fieldName => $fieldConfig) {
            $fields[$fieldName] = $this->mappingFieldFactory->create(['name' => $fieldName] + $fieldConfig);
        }

        return $fields;
    }

    /**
     * Check if a datasource can be used as a dynamic fields provider.
     *
     * @param mixed $datasource Datasource to be checked.
     *
     * @return boolean
     */
    private function isDynamicFieldsProvider($datasource)
    {
        return $datasource instanceof DynamicFieldProviderInterface;
    }
}
