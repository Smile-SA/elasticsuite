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

use \Smile\ElasticsuiteCore\Index\Index;
use Smile\ElasticsuiteCore\Api\Index\TypeInterface;

/**
 * Index test case.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class IndexTest extends \PHPUnit_Framework_TestCase
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
        $typeStub = $this->getMock(TypeInterface::class);
        $typeStub->method('getName')->will($this->returnValue('type'));
        $this->index = new Index('identifier', 'name', [$typeStub], 'type');
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
        new Index('identifier', 'name', [], 'defaultSearchType');
    }
}
