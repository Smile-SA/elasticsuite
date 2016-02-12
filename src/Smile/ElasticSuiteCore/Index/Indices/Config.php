<?php

namespace Smile\ElasticSuiteCore\Index\Indices;

use Smile\ElasticSuiteCore\Index\Indices\Config\Reader;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticSuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;

class Config extends \Magento\Framework\Config\Data
{
    /** Cache ID for Search Request*/
    const CACHE_ID = 'indices_config';

    /**
     * @var \Magento\Framework\ObjectManagerInterface;
     */
    private $objectManager;

    private $typeFactory;
    private $mappingFactory;
    private $mappingFieldFactory;

    /**
     * @param \Magento\Framework\Search\Request\Config\FilesystemReader $reader
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param string $cacheId
     */
    public function __construct(Reader $reader, CacheInterface $cache, ObjectManagerInterface $objectManager, $cacheId = self::CACHE_ID) {
        $this->objectManager = $objectManager;
        $this->initFactories();
        parent::__construct($reader, $cache, $cacheId);
    }

    /**
     * Initialise data for configuration
     * @return void
     */
    protected function initData()
    {
        parent::initData();
        $this->_data = array_map([$this, 'initIndexConfig'], $this->_data);
    }

    private function initFactories()
    {
        $this->typeFactory = $this->objectManager->get('Smile\ElasticSuiteCore\Api\Index\TypeInterfaceFactory');
        $this->mappingFactory = $this->objectManager->get('Smile\ElasticSuiteCore\Api\Index\MappingInterfaceFactory');
        $this->mappingFieldFactory = $this->objectManager->get('Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterfaceFactory');
    }

    /**
     *
     * @param array
     *
     * @return array
     */
    private function initIndexConfig($indexConfigData) {

        $types       = [];

        foreach ($indexConfigData['types'] as $typeName => $typeConfigData) {

            $datasources  = [];
            $staticFields = [];

            foreach ($typeConfigData['datasources'] as $datasourceName => $datasourceClass) {
                $datasources[$datasourceName] = $this->objectManager->get($datasourceClass);
            }

            $dynamicFieldProviders = array_filter($datasources, array($this, 'isDynamicFieldsProvider'));

            foreach ($typeConfigData['mapping']['staticFields'] as $fieldName => $fieldConfig) {
                $staticFields[$fieldName] = $this->mappingFieldFactory->create($fieldConfig + ['name' => $fieldName]);
            }

            $mapping = $this->mappingFactory->create(
                ['staticFields' => $staticFields, 'dynamicFieldProviders' => $dynamicFieldProviders]
            );

            $types[$typeName] = $this->typeFactory->create(
                ['name' => $typeName, 'datasources' => $datasources, 'mapping' => $mapping]
            );
        }

        return ['types' => $types];
    }

    private function isDynamicFieldsProvider($datasource)
    {
        return $datasource instanceOf DynamicFieldProviderInterface;
    }

}
