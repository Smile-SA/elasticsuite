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
            $staticFields = [];

            foreach ($typeConfigData['datasources'] as $datasourceName => $datasourceClass) {
                $datasources[$datasourceName] = $this->objectManager->get($datasourceClass);
            }

            $dynamicFieldProviders = array_filter($datasources, array($this, 'isDynamicFieldsProvider'));

            foreach ($typeConfigData['mapping']['staticFields'] as $fieldName => $fieldConfig) {
                $staticFields[$fieldName] = $this->mappingFieldFactory->create(['name' => $fieldName] + $fieldConfig);
            }

            $mapping = $this->mappingFactory->create(
                [
                    'idFieldName'           => $typeConfigData['idFieldName'],
                    'staticFields'          => $staticFields,
                    'dynamicFieldProviders' => $dynamicFieldProviders,
                ]
            );

            $types[$typeName] = $this->typeFactory->create(
                ['name' => $typeName, 'mapping' => $mapping, 'datasources' => $datasources]
            );
        }

        $defaultSearchType = $indexConfigData['defaultSearchType'];

        return ['types' => $types, 'defaultSearchType' => $defaultSearchType];
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
