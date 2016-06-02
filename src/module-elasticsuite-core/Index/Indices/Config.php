<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Index\Indices;

use Smile\ElasticSuiteCore\Index\Indices\Config\Reader;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticSuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;

/**
 * ElasticSuite indices configuration;
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 *
 * @category Smile_ElasticSuite
 * @package  Smile_ElasticSuiteCore
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
     * @var \Smile\ElasticSuiteCore\Api\Index\TypeInterfaceFactory
     */
    private $typeFactory;

    /**
     * Factory used to build mappings.
     *
     * @var \Smile\ElasticSuiteCore\Api\Index\MappingInterfaceFactory
     */
    private $mappingFactory;

    /**
     * Factory used to build mapping fields.
     *
     * @var \Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterfaceFactory
     */
    private $mappingFieldFactory;

    /**
     * Instanciate config.
     *
     * @param Reader                 $reader        Config file reader.
     * @param CacheInterface         $cache         Cache instance.
     * @param ObjectManagerInterface $objectManager Object manager (used to instanciate several factories)
     * @param string                 $cacheId       Default config cache id.
     */
    public function __construct(
        Reader $reader,
        CacheInterface $cache,
        ObjectManagerInterface $objectManager,
        $cacheId = self::CACHE_ID
    ) {
        $this->objectManager = $objectManager;
        $this->initFactories();
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
     * Init factories used by the configuration to build types, mappings and fields objects.
     *
     * @return void
     */
    private function initFactories()
    {
        $this->typeFactory = $this->objectManager->get(
            'Smile\ElasticSuiteCore\Api\Index\TypeInterfaceFactory'
        );

        $this->mappingFactory = $this->objectManager->get(
            'Smile\ElasticSuiteCore\Api\Index\MappingInterfaceFactory'
        );

        $this->mappingFieldFactory = $this->objectManager->get(
            'Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterfaceFactory'
        );
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
                $staticFields[$fieldName] = $this->mappingFieldFactory->create($fieldConfig + ['name' => $fieldName]);
            }

            $mapping = $this->mappingFactory->create(
                [
                    'staticFields'          => $staticFields,
                    'dynamicFieldProviders' => $dynamicFieldProviders,
                    'idFieldName'           => $typeConfigData['idFieldName'],
                ]
            );

            $types[$typeName] = $this->typeFactory->create(
                ['name' => $typeName, 'datasources' => $datasources, 'mapping' => $mapping]
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
