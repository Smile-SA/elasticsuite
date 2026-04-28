<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Test\Unit\Index\Indices;

use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Smile\ElasticsuiteCore\Index\Indices\Config\Converter;
use Smile\ElasticsuiteCore\Api\Index\MappingInterfaceFactory as MappingFactory;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterfaceFactory as MappingFieldFactory;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Api\Index\DataSourceResolverInterfaceFactory as DataSourceResolverFactory;
use Smile\ElasticsuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;
use Smile\ElasticsuiteCore\Index\Mapping;
use Smile\ElasticsuiteCore\Index\Mapping\Field;
use Smile\ElasticsuiteCore\Index\DataSourceResolver;
use Smile\ElasticsuiteCore\Index\Indices\Config;

/**
 * Indices configuration test case.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    protected $parsedData;

    /**
     * Tests that the indices configuration is correctly built and returned.
     * @dataProvider stringFieldDynamicConfigProvider
     *
     * Verifies that:
     * - The configuration is not empty.
     * - Both 'index1' and 'index2' are present in the configuration.
     * - 'index2' contains a 'mapping' key holding a valid {@see Mapping} instance.
     * - The mapping for 'index2' contains fields.
     * - The 'stringField' field in 'index2' mapping is a valid {@see Field} instance with
     *   - its type set to 'string',
     *   - its searchability set to true,
     *   whatever the dynamic configuration provided, confirming that the statically configured field type
     *   and field properties takes precedence over the dynamically provided field type and/or properties.
     *
     * @param array $stringFieldDynamicConfig Dynamic configuration for the stringField field in 'index2'.
     *
     * @return void
     */
    public function testConfig($stringFieldDynamicConfig)
    {
        $config = new Config(
            $this->getConfigReaderMock(),
            $this->getCacheMock(),
            $this->getMappingFactoryMock(),
            $this->getMappingFieldFactoryMock(),
            $this->getDataSourceResolverFactoryMock($stringFieldDynamicConfig),
            $this->getSerializerMock()
        );

        $indicesConfig = $config->get();
        $this->assertNotEmpty($indicesConfig);

        $this->assertArrayHasKey('index1', $indicesConfig);
        $this->assertArrayHasKey('index2', $indicesConfig);
        $this->assertArrayHasKey('mapping', $indicesConfig['index2']);
        $this->assertInstanceOf(Mapping::class, $indicesConfig['index2']['mapping']);
        $this->assertNotEmpty($indicesConfig['index2']['mapping']->getFields());

        $stringField = $indicesConfig['index2']['mapping']->getField('stringField');
        $this->assertInstanceOf(Field::class, $stringField);
        $this->assertEquals('string', $stringField->getType());
        $this->assertTrue($stringField->isSearchable());

        if (array_key_exists('fieldConfig', $stringFieldDynamicConfig)) {
            $keptDynamicConfig = array_diff_key($stringFieldDynamicConfig['fieldConfig'], ['is_searchable' => false]);
            if (!empty($keptDynamicConfig)) {
                $fieldConfig = $stringField->getConfig();
                foreach ($keptDynamicConfig as $key => $value) {
                    $this->assertEquals($value, $fieldConfig[$key], 'Kept dynamic field property');
                }
            }
        }
    }

    /**
     * Provides dynamic configuration sets for the 'stringField' field in 'index2'.
     *
     * @return array
     */
    public function stringFieldDynamicConfigProvider()
    {
        return [
            // Dynamic configuration for the stringField field in 'index2'.
            [['type' => FieldInterface::FIELD_TYPE_KEYWORD]],
            [['type' => FieldInterface::FIELD_TYPE_TEXT]],
            [['fieldConfig' => ['is_searchable' => false, 'default_search_analyzer' => FieldInterface::ANALYZER_SHINGLE]]],
            [['type' => FieldInterface::FIELD_TYPE_KEYWORD, 'fieldConfig' => ['is_searchable' => false]]],
            [
                [
                    'type' => FieldInterface::FIELD_TYPE_KEYWORD,
                    'fieldConfig' => ['default_search_analyzer' => FieldInterface::ANALYZER_EDGE_NGRAM],
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $xml = new \DOMDocument();
        $xml->load(__DIR__ . '/Config/elasticsuite_indices.xml');
        $converter = new Converter();
        $this->parsedData = $converter->convert($xml);
    }

    /**
     * Creates and returns a mock of the indices configuration reader
     * with data coming from the sample elasticsuite_indices.xml already parsed in the setUp method.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject Mock instance of Config\Reader.
     */
    protected function getConfigReaderMock()
    {
        $reader = $this->getMockBuilder(Config\Reader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reader->method('read')->willReturn($this->parsedData);

        return $reader;
    }

    /**
     * Creates and returns a mock of the cache interface.
     *
     * The mock is configured so that the `load` method always returns false,
     * simulating a cache miss to ensure the configuration is always read
     * from the reader rather than from the cache.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject Mock instance of CacheInterface.
     */
    protected function getCacheMock()
    {
        $cache = $this->getMockBuilder(CacheInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cache->method('load')->willReturn(false);

        return $cache;
    }

    /**
     * Creates and returns a mock of the mapping factory.
     *
     * The mock is configured so that the `create` method instantiates and returns
     * a real {@see Mapping} object using the provided arguments, allowing the factory
     * to behave as closely as possible to the actual implementation during testing.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject Mock instance of MappingFactory.
     */
    protected function getMappingFactoryMock()
    {
        $mappingFactory = $this->getMockBuilder(MappingFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mappingFactory->method('create')->willReturnCallback(function ($args) {
            return new Mapping(...array_values($args));
        });

        return $mappingFactory;
    }

    /**
     * Creates and returns a mock of the mapping field factory.
     *
     * The mock is configured so that the `create` method instantiates and returns
     * a real {@see Field} object using the provided arguments, allowing the factory
     * to behave as closely as possible to the actual implementation during testing.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject Mock instance of MappingFieldFactory.
     */
    protected function getMappingFieldFactoryMock()
    {
        $mappingFieldFactory = $this->getMockBuilder(MappingFieldFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mappingFieldFactory->method('create')->willReturnCallback(function ($args) {
            return new Field(...$args);
        });

        return $mappingFieldFactory;
    }

    /**
     * Creates and returns a mock of the data source resolver factory.
     *
     * The mock is configured so that the `create` method returns a mock of
     * {@see DataSourceResolver}, allowing the factory to simulate the creation
     * of data source resolver instances during testing without relying on the
     * actual implementation or its dependencies.
     *
     * @param array $stringFieldDynamicConfig Dynamic configuration for the stringField field in 'index2'.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject Mock instance of DataSourceResolverFactory.
     */
    protected function getDataSourceResolverFactoryMock($stringFieldDynamicConfig)
    {
        $dataSourceResolverFactory = $this->getMockBuilder(DataSourceResolverFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataSourceResolverFactory->method('create')->willReturnCallback(
            function () use ($stringFieldDynamicConfig) {
                return $this->getDataSourceResolverMock($stringFieldDynamicConfig);
            }
        );

        return $dataSourceResolverFactory;
    }

    /**
     * Creates and returns a mock of the data source resolver.
     *
     * The mock is configured so that the `getDataSources` method returns different
     * results based on the provided index name:
     * - For 'index2', it returns an array containing a dynamic data source mock.
     * - For any other index name (e.g. 'index1'), it returns an empty array.
     *
     * Note: The test XML file declares two types under 'index1', which is no longer
     * supported. This would cause the mapping for 'index1' to be based on the last
     * declared type, which does not include the static field 'stringField'. Returning
     * an empty array for 'index1' reflects this unsupported scenario.
     *
     * @param array $stringFieldDynamicConfig Dynamic configuration for the stringField field in 'index2'.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject Mock instance of DataSourceResolver.
     */
    protected function getDataSourceResolverMock($stringFieldDynamicConfig)
    {
        $dataSourceResolver = $this->getMockBuilder(DataSourceResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataSourceResolver->method('getDataSources')->willReturnCallback(
            function ($indexName) use ($stringFieldDynamicConfig) {
                /*
                 * Test XML file has two types under 'index1' which is not supposed to be supported anymore,
                 * and would lead to the mapping for 'index1' being the one of the last declared type which
                 * does not contain the static field 'stringField'
                 */
                if ($indexName === 'index2') {
                    return [$this->getDynamicDatasourceMock($stringFieldDynamicConfig)];
                }

                return [];
            }
        );

        return $dataSourceResolver;
    }

    /**
     * Creates and returns a mock of a dynamic data source.
     *
     * The mock is configured so that the `getFields` method returns an array
     * containing a single dynamic field named 'stringField', typed as {@see FieldInterface::FIELD_TYPE_KEYWORD}.
     * This type intentionally differs from the type declared in the elasticsuite_indices.xml configuration file,
     * allowing tests to verify that dynamic fields provided at runtime can supplement statically configured fields
     * but that statically configured fields take precedence.
     *
     * @param array $stringFieldDynamicConfig Dynamic configuration for the stringField field in 'index2'.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject Mock instance of DynamicFieldProviderInterface.
     */
    protected function getDynamicDatasourceMock($stringFieldDynamicConfig)
    {
        // Mock dynamic field with a different type than in the elasticsuite_indices.xml config file.
        $dynamicFields = [
            'stringField' => $this->getMappingFieldFactoryMock()->create(
                ['name' => 'stringField'] + $stringFieldDynamicConfig
            ),
        ];

        $datasource = $this->getMockBuilder(DynamicFieldProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $datasource->method('getFields')->willReturn($dynamicFields);

        return $datasource;
    }

    /**
     * Creates and returns a mock of the serializer interface.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject Mock instance of SerializerInterface.
     */
    protected function getSerializerMock()
    {
        return $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
