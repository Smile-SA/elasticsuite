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

namespace Smile\ElasticsuiteCore\Index\Indices;

use Smile\ElasticsuiteCore\Index\Indices\Config\Reader;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;
use Smile\ElasticsuiteCore\Api\Index\TypeInterfaceFactory as TypeFactory;
use Smile\ElasticsuiteCore\Api\Index\MappingInterfaceFactory as MappingFactory;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterfaceFactory as MappingFieldFactory;

/**
 * ElasticSuite indices configuration;
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 *
 * @category Smile_Elasticsuite
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
     * Object manager.
     *
     * @var \Magento\Framework\ObjectManagerInterface;
     */
    private $objectManager;

    /**
     * Factory used to build mapping types.
     *
     * @var \Smile\ElasticsuiteCore\Api\Index\TypeInterfaceFactory
     */
    private $typeFactory;

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
     * Instanciate config.
     *
     * @param Reader                 $reader              Config file reader.
     * @param CacheInterface         $cache               Cache instance.
     * @param ObjectManagerInterface $objectManager       Object manager (used to instanciate several factories)
     * @param TypeFactory            $typeFactory         Index type factory.
     * @param MappingFactory         $mappingFactory      Index mapping factory.
     * @param MappingFieldFactory    $mappingFieldFactory Index mapping field factory.
     * @param string                 $cacheId             Default config cache id.
     */
    public function __construct(
        Reader $reader,
        CacheInterface $cache,
        ObjectManagerInterface $objectManager,
        TypeFactory $typeFactory,
        MappingFactory $mappingFactory,
        MappingFieldFactory $mappingFieldFactory,
        $cacheId = self::CACHE_ID
    ) {
        $this->typeFactory         = $typeFactory;
        $this->mappingFactory      = $mappingFactory;
        $this->mappingFieldFactory = $mappingFieldFactory;
        $this->objectManager       = $objectManager;

        parent::__construct($reader, $cache, $cacheId);
    }

    /**
     * Init data for configuration.
     *
     * @return void
     */
    protected function initData()
    {
        parent::initData();
        $this->_data = array_map([$this, 'initIndexConfig'], $this->_data);
    }

    /**
     * Init type, mapping, and fields from a index configuration array.
     *
     * @param array $indexConfigData Processed index configuration.
     *
     * @return array
     */
    private function initIndexConfig(array $indexConfigData)
    {
        $types = [];

        foreach ($indexConfigData['types'] as $typeName => $typeConfigData) {
            $datasources  = [];

            foreach ($typeConfigData['datasources'] as $datasourceName => $datasourceClass) {
                $datasources[$datasourceName] = $this->objectManager->get($datasourceClass);
            }

            $fields  = $this->getMappingFields($typeConfigData, $datasources);

            $mapping = $this->mappingFactory->create(
                ['idFieldName' => $typeConfigData['idFieldName'], 'fields' => $fields]
            );

            $types[$typeName] = $this->typeFactory->create(
                ['name' => $typeName, 'mapping' => $mapping, 'datasources' => $datasources]
            );
        }

        if (count($types) > 1) {
            throw new \LogicException("Can not add several types in the same index.");
        }

        $defaultSearchType = $indexConfigData['defaultSearchType'];

        return ['types' => $types, 'defaultSearchType' => $defaultSearchType];
    }

    /**
     * Prepare mapping fields by merging static fields with dynamic ones.
     *
     * @param array $typeConfigData Processed type configuration.
     * @param array $dataSources    Data sources for current type.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface[]
     */
    private function getMappingFields($typeConfigData, $dataSources)
    {
        /** @var DynamicFieldProviderInterface[] $dynamicFieldProviders */
        $dynamicFieldProviders = array_filter($dataSources, [$this, 'isDynamicFieldsProvider']);

        $fields = [];

        foreach ($dynamicFieldProviders as $dynamicFieldProvider) {
            $fields += $dynamicFieldProvider->getFields();
        }

        foreach ($typeConfigData['mapping']['staticFields'] as $fieldName => $fieldConfig) {
            if (isset($fields[$fieldName])) {
                // Field also exists with dynamic providers.
                // We merge the dynamic config and the config coming from configuration file.
                // XML file has precedence.
                $config                     = $fields[$fieldName]->getConfig();
                $fieldConfig['fieldConfig'] = array_merge($config, $fieldConfig['fieldConfig'] ?? []);
            }

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
