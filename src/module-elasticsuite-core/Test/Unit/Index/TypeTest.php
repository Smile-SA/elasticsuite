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
namespace Smile\ElasticsuiteCore\Test\Unit\Index;

use \Smile\ElasticsuiteCore\Index\Type;
use Smile\ElasticsuiteCore\Api\Index\FieldInterface;
use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;

/**
 * Index type test case.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class TypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Smile\ElasticsuiteCore\Index\Type
     */
    private $type;

    /**
     * Create a minimal type to run the tests.
     *
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $fieldMock   = $this->getMockBuilder(FieldInterface::class)->getMock();

        $mappingMock = $this->getMockBuilder(MappingInterface::class)->getMock();
        $mappingMock->method('getIdField')->will($this->returnValue($fieldMock));

        $datasourcesMock = ['datasource' => $this->getMockBuilder(DatasourceInterface::class)->getMock()];

        $this->type = new \Smile\ElasticsuiteCore\Index\Type('typeName', $mappingMock, $datasourcesMock);
    }

    /**
     * Test basic getter for the type class.
     *
     * @return void
     */
    public function testGetters()
    {
        $this->assertEquals('typeName', $this->type->getName());
        $this->assertInstanceOf(MappingInterface::class, $this->type->getMapping());
        $this->assertInstanceOf(FieldInterface::class, $this->type->getIdField());
        $this->assertCount(1, $this->type->getDatasources());
        $this->assertInstanceOf(DatasourceInterface::class, $this->type->getDatasource('datasource'));
    }

    /**
     * Trying to access an invalid datasource.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Datasource invalidDatasource does not exists.
     *
     * @return void
     */
    public function testInvalidDatasource()
    {
        $this->type->getDatasource('invalidDatasource');
    }
}
