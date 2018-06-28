<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Index\Indices\Config;

use Smile\ElasticsuiteCore\Index\Indices\Config\Converter;

/**
 * Analysis configuration file converter test case.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    private $parsedData;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $xml = new \DOMDocument();
        $xml->load(__DIR__ . '/elasticsuite_indices.xml');
        $converter = new Converter();
        $this->parsedData = $converter->convert($xml);
    }

    /**
     * Test available indices.
     *
     * @return void
     */
    public function testAvailableIndices()
    {
        $this->assertCount(2, $this->parsedData);
        $this->assertArrayHasKey('index1', $this->parsedData);
        $this->assertArrayHasKey('index2', $this->parsedData);
    }

    /**
     * Test available types in the first index.
     *
     * @return void
     */
    public function testAvailableTypes()
    {
        $indexData = $this->getIndexData('index1');
        $this->assertCount(2, $indexData['types']);
        $this->assertEquals('simpleType', $indexData['defaultSearchType']);
    }

    /**
     * Test simple type structure.
     *
     * @return void
     */
    public function testSimpleType()
    {
        $typeData = $this->getTypeData('index1', 'simpleType');
        $this->assertEquals('idField', $typeData['idFieldName']);
        $this->assertArrayHasKey('mapping', $typeData);
        $this->assertArrayHasKey('datasources', $typeData);
        $this->assertCount(0, $typeData['datasources']);
    }

    /**
     * Test simple type mapping structure.
     *
     * @return void
     */
    public function testSimpleTypeMapping()
    {
        $indexData = $this->getIndexData('index1');
        $this->assertArrayHasKey('simpleType', $indexData['types']);

        $mappingData = $this->getTypeMappingData('index1', 'simpleType');

        $this->assertArrayHasKey('staticFields', $mappingData);
        $this->assertCount(2, $mappingData['staticFields']);

        $this->assertArrayHasKey('idField', $mappingData['staticFields']);
        $this->assertEquals('integer', $mappingData['staticFields']['idField']['type']);

        $this->assertArrayHasKey('stringField', $mappingData['staticFields']);
        $this->assertEquals('string', $mappingData['staticFields']['stringField']['type']);

        $this->assertArrayHasKey('dynamicFieldProviders', $mappingData);
        $this->assertCount(0, $mappingData['dynamicFieldProviders']);
    }

    /**
     * Test datasources in complex type.
     *
     * @return void
     */
    public function testDatasources()
    {
        $indexData = $this->getIndexData('index1');
        $this->assertArrayHasKey('complexType', $indexData['types']);

        $typeData = $this->getTypeData('index1', 'complexType');
        $this->assertArrayHasKey('datasources', $typeData);

        $datasources = $typeData['datasources'];
        $this->assertArrayHasKey('ds1', $datasources);
        $this->assertEquals('DatasourceClass1', $datasources['ds1']);
        $this->assertArrayHasKey('ds2', $datasources);
        $this->assertEquals('DatasourceClass2', $datasources['ds2']);
    }

    /**
     * Test nested field parsing.
     *
     * @return void
     */
    public function testNestedField()
    {
        $mappingData = $this->getTypeMappingData('index1', 'complexType');
        $this->assertArrayHasKey('nested.child', $mappingData['staticFields']);
        $this->assertEquals('nested', $mappingData['staticFields']['nested.child']['nestedPath']);
    }

    /**
     * Test field params parsing.
     *
     * @return void
     */
    public function testFieldParams()
    {
        $mappingData = $this->getTypeMappingData('index1', 'complexType');
        $this->assertArrayHasKey('fieldWithParams', $mappingData['staticFields']);
        $fieldData = $mappingData['staticFields']['fieldWithParams'];

        $this->assertArrayHasKey('fieldConfig', $fieldData);
        $fieldConfig = $fieldData['fieldConfig'];

        $this->assertCount(2, $fieldConfig);
        $this->assertArrayHasKey('param1', $fieldConfig);
        $this->assertArrayHasKey('param2', $fieldConfig);
        $this->assertEquals('value1', $fieldConfig['param1']);
        $this->assertEquals('value2', $fieldConfig['param2']);
    }

    /**
     * Get data parsed for the required index.
     *
     * @param string $indexName Index name.
     *
     * @return array
     */
    private function getIndexData($indexName)
    {
        return $this->parsedData[$indexName];
    }

    /**
     * Get data parsed for the required type.
     *
     * @param string $indexName Index name.
     * @param string $typeName  Type name.
     *
     * @return array
     */
    private function getTypeData($indexName, $typeName)
    {
        return $this->getIndexData($indexName)['types'][$typeName];
    }

    /**
     * Get mapping data parsed for the required type.
     *
     * @param string $indexName Index name.
     * @param string $typeName  Type name.
     *
     * @return array
     */
    private function getTypeMappingData($indexName, $typeName)
    {
        return $this->getTypeData($indexName, $typeName)['mapping'];
    }
}
