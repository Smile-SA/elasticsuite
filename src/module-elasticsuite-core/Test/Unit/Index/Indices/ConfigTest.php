<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Index\Indices;

use Smile\ElasticsuiteCore\Index\Indices\Config;

/**
 * Indices configuration configuration test case.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $readerData = [
        'index' => [
            'defaultSearchType' => 'simpleType',
            'types'             => [
                'simpleType'  => [
                    'idFieldName' => 'idField',
                    'datasources' => ['ds1' => 'DatasourceClass1', 'ds2' => 'DatasourceClass2'],
                    'mapping'     => ['staticFields' => ['idField' => ['type' => 'integer']]],
                ],
            ],
        ],
    ];

    /**
     * Test index configuration existing indices.
     *
     * @return void
     */
    public function testGetConfig()
    {
        $config = $this->getConfig()->get();
        $this->assertCount(1, $config);
        $this->assertArrayHasKey('index', $config);
        $this->assertArrayNotHasKey('missing_index', $config);
    }

    /**
     * Test configured index structure.
     *
     * @return void
     */
    public function testGetIndexConfig()
    {
        $config = $this->getConfig();
        $indexConfig = $config->get('index');
        $this->assertNotNull($indexConfig);
        $this->assertEquals('simpleType', $indexConfig['defaultSearchType']);
        $this->assertArrayHasKey('simpleType', $indexConfig['types']);
        $this->assertInstanceOf(\Smile\ElasticsuiteCore\Api\Index\TypeInterface::class, $indexConfig['types']['simpleType']);
    }

    /**
     * Test access to a missing index.
     *
     * @return void
     */
    public function testGetMissingIndexConfig()
    {
        $config = $this->getConfig();
        $indexConfig = $config->get('not_existing_index');
        $this->assertNull($indexConfig);
    }

    /**
     * Init a new config with mock data.
     *
     * @return \Smile\ElasticsuiteCore\Index\Indices\Config
     */
    private function getConfig()
    {
        $readerMock        = $this->getReaderMock();
        $cacheMock         = $this->getCacheMock();
        $objectManagerMock = $this->getObjectManagerMock();

        $typeFactory = $this->getFactory(
            \Smile\ElasticsuiteCore\Api\Index\TypeInterface::class,
            \Smile\ElasticsuiteCore\Index\Type::class
        );

        $mappingFactory = $this->getFactory(
            \Smile\ElasticsuiteCore\Api\Index\MappingInterface::class,
            \Smile\ElasticsuiteCore\Index\Mapping::class
        );

        $fieldFactory = $this->getFactory(
            \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface::class,
            \Smile\ElasticsuiteCore\Index\Mapping\Field::class
        );

        return new Config($readerMock, $cacheMock, $objectManagerMock, $typeFactory, $mappingFactory, $fieldFactory);
    }

    /**
     * Mock the config reader.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getReaderMock()
    {
        $readerMock = $this->getMockBuilder(\Smile\ElasticsuiteCore\Index\Indices\Config\Reader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $readerMock->method('read')
            ->will($this->returnValue($this->readerData));

        return $readerMock;
    }

    /**
     * Mock the config cache.
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getCacheMock()
    {
        $cacheMock = $this->getMockBuilder(\Magento\Framework\Config\CacheInterface::class)->getMock();
        $cacheMock->method('load')->will($this->returnValue(false));

        return $cacheMock;
    }

    /**
     * Mock the object manager.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getObjectManagerMock()
    {
        $objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)->getMock();

        return $objectManagerMock;
    }

    /**
     * Factory implementation.
     *
     * @param string $name  Interface name.
     * @param string $class Implementation.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getFactory($name, $class)
    {
        $factory = $this->getMockBuilder($name . 'Factory')
            ->setMethods(['create'])
            ->getMock();

        $createMethod = function ($args) use ($class) {
            $class = new \ReflectionClass($class);

            return $class->newInstanceArgs($args);
        };

        $factory->method('create')->will($this->returnCallback($createMethod));

        return $factory;
    }
}
