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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Index;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use \Smile\ElasticsuiteCore\Index\Index;
use Smile\ElasticsuiteCore\Api\Index\TypeInterface;

/**
 * Index test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class IndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Smile\ElasticsuiteCore\Index\Index
     */
    private $index;

    /**
     * Create a minimal index to run the tests.
     *
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $fieldMock   = $this->getMockBuilder(FieldInterface::class)->getMock();

        $mappingMock = $this->getMockBuilder(MappingInterface::class)->getMock();
        $mappingMock->method('getIdField')->will($this->returnValue($fieldMock));

        $typeStub = $this->getMockBuilder(TypeInterface::class)->getMock();
        $typeStub->method('getName')->will($this->returnValue('type'));

        $this->index = new Index('identifier', 'name', [$typeStub], 'type', $mappingMock);
    }

    /**
     * Test basic getters.
     *
     * @return void
     */
    public function testGetters()
    {
        $this->assertEquals('name', $this->index->getName());
        $this->assertEquals('identifier', $this->index->getIdentifier());
        $this->assertEquals(false, $this->index->needInstall());
        $this->assertCount(1, $this->index->getTypes());
        $this->assertInstanceOf(TypeInterface::class, $this->index->getType('type'));
        $this->assertInstanceOf(TypeInterface::class, $this->index->getDefaultSearchType());
        $this->assertInstanceOf(MappingInterface::class, $this->index->getMapping());
        $this->assertInstanceOf(FieldInterface::class, $this->index->getIdField());
    }

    /**
     * Test an exception is raised when accessing a missign type.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExcpetionMessage Type invalidType does not exists in the index.
     *
     * @return void
     */
    public function testInvalidTypeAccess()
    {
        $this->index->getType('invalidType');
    }

    /**
     * Test an exception is raised when init an index with an invalid default search type.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExcpetionMessage Default search defaultSearchType does not exists in the index.
     *
     * @return void
     */
    public function testInvalidDefaultSearchType()
    {
        $fieldMock   = $this->getMockBuilder(FieldInterface::class)->getMock();
        $mappingMock = $this->getMockBuilder(MappingInterface::class)->getMock();
        $mappingMock->method('getIdField')->will($this->returnValue($fieldMock));

        new Index('identifier', 'name', [], 'defaultSearchType', $mappingMock);
    }
}
