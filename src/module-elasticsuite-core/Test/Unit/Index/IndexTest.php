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
 * @copyright 2020 Smile
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
    protected function setUp(): void
    {
        $fieldMock   = $this->getMockBuilder(FieldInterface::class)->getMock();

        $mappingMock = $this->getMockBuilder(MappingInterface::class)->getMock();
        $mappingMock->method('getIdField')->will($this->returnValue($fieldMock));


        $this->index = new Index('identifier', 'name', 'type', $mappingMock);
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
        $this->assertInstanceOf(MappingInterface::class, $this->index->getMapping());
        $this->assertInstanceOf(FieldInterface::class, $this->index->getIdField());
    }
}
