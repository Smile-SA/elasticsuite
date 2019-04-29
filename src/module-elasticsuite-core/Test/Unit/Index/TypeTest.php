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

use \Smile\ElasticsuiteCore\Index\Type;
use Smile\ElasticsuiteCore\Api\Index\FieldInterface;
use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;

/**
 * Index type test case.
 *
 * @category  Smile
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

        $this->type = new \Smile\ElasticsuiteCore\Index\Type('typeName', $mappingMock);
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
    }
}
