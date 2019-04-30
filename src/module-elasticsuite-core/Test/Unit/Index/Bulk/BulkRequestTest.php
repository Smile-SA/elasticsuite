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
namespace Smile\ElasticsuiteCore\Test\Unit\Index\Bulk;

use Smile\ElasticsuiteCore\Index\Bulk\BulkRequest;
use Smile\ElasticsuiteCore\Api\Index\IndexInterface;
use Smile\ElasticsuiteCore\Api\Index\TypeInterface;

/**
 * Bulk request test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class BulkRequestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexInterface
     */
    private $index;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\TypeInterface
     */
    private $type;

    /**
     * Create required stubs.
     *
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->index = $this->getMockBuilder(IndexInterface::class)->getMock();
        $this->index->method('getName')->will($this->returnValue('indexName'));

        $this->type = $this->getMockBuilder(TypeInterface::class)->getMock();
        $this->type->method('getName')->will($this->returnValue('typeName'));
    }

    /**
     * Test bulk request is empty by default.
     *
     * @return void
     */
    public function testEmptyOnCreate()
    {
        $this->assertEquals(true, $this->createBulk()->isEmpty());
    }

    /**
     * Test adding a document to a bulk request generate a valid ES bulk.
     *
     * @return void
     */
    public function testAddDocument()
    {
        $bulkRequest = $this->createBulk();
        $bulkRequest->addDocument($this->index, $this->type, 'docId', ['foo' => 'bar']);

        $this->assertEquals(false, $bulkRequest->isEmpty());
        $this->assertCount(2, $bulkRequest->getOperations());

        list($head, $data) = $bulkRequest->getOperations();

        $this->assertArrayHasKey('index', $head);
        $this->assertEquals('docId', $head['index']['_id']);
        $this->assertEquals($this->index->getName(), $head['index']['_index']);
        $this->assertEquals($this->type->getName(), $head['index']['_type']);
        $this->assertEquals(['foo' => 'bar'], $data);
    }

    /**
     * Test adding several documents to a bulk request generate a valid ES bulk.
     *
     * @return void
     */
    public function testAddDocuments()
    {
        $bulkRequest = $this->createBulk();
        $docCount    = 2;

        $data = [];
        for ($i = 1; $i <= $docCount; $i++) {
            $data['docId' . $i] = ['foo' => $i];
        }
        $bulkRequest->addDocuments($this->index, $this->type, $data);

        $operations = $bulkRequest->getOperations();
        $this->assertEquals(false, $bulkRequest->isEmpty());
        $this->assertCount($docCount * 2, $operations);

        for ($i = 1; $i <= $docCount; $i++) {
            $head = current($operations);
            next($operations);
            $data = current($operations);
            next($operations);
            $this->assertArrayHasKey('index', $head);
            $this->assertEquals('docId' . $i, $head['index']['_id']);
            $this->assertEquals($this->index->getName(), $head['index']['_index']);
            $this->assertEquals($this->type->getName(), $head['index']['_type']);
            $this->assertEquals(['foo' => $i], $data);
        }
    }

    /**
     * Test deleting a document to a bulk request generate a valid ES bulk.
     *
     * @return void
     */
    public function testDeleteDocument()
    {
        $bulkRequest = $this->createBulk();
        $bulkRequest->deleteDocument($this->index, $this->type, 'docId');

        $this->assertEquals(false, $bulkRequest->isEmpty());
        $this->assertCount(1, $bulkRequest->getOperations());

        $head = current($bulkRequest->getOperations());

        $this->assertArrayHasKey('delete', $head);
        $this->assertEquals('docId', $head['delete']['_id']);
        $this->assertEquals($this->index->getName(), $head['delete']['_index']);
        $this->assertEquals($this->type->getName(), $head['delete']['_type']);
    }

    /**
     * Test deleting several documents to a bulk request generate a valid ES bulk.
     *
     * @return void
     */
    public function testDeleteDocuments()
    {
        $bulkRequest = $this->createBulk();
        $docCount    = 2;

        $docIds = [];
        for ($i = 1; $i <= $docCount; $i++) {
            $docIds[] = 'docId' . $i;
        }
        $bulkRequest->deleteDocuments($this->index, $this->type, $docIds);

        $operations = $bulkRequest->getOperations();
        $this->assertEquals(false, $bulkRequest->isEmpty());
        $this->assertCount($docCount, $operations);

        foreach ($docIds as $currentId) {
            $head = current($operations);
            next($operations);
            $this->assertArrayHasKey('delete', $head);
            $this->assertEquals($currentId, $head['delete']['_id']);
            $this->assertEquals($this->index->getName(), $head['delete']['_index']);
            $this->assertEquals($this->type->getName(), $head['delete']['_type']);
        }
    }

    /**
     * Test updating a document to a bulk request generate a valid ES bulk.
     *
     * @return void
     */
    public function testUpdateDocument()
    {
        $bulkRequest = $this->createBulk();
        $bulkRequest->updateDocument($this->index, $this->type, 'docId', ['foo' => 'bar']);

        $this->assertEquals(false, $bulkRequest->isEmpty());
        $this->assertCount(2, $bulkRequest->getOperations());

        list($head, $data) = $bulkRequest->getOperations();

        $this->assertArrayHasKey('update', $head);
        $this->assertEquals('docId', $head['update']['_id']);
        $this->assertEquals($this->index->getName(), $head['update']['_index']);
        $this->assertEquals($this->type->getName(), $head['update']['_type']);
        $this->assertEquals(['doc' => ['foo' => 'bar']], $data);
    }

    /**
     * Test updating several documents to a bulk request generate a valid ES bulk.
     *
     * @return void
     */
    public function testUpdateDocuments()
    {
        $bulkRequest = $this->createBulk();
        $docCount    = 2;

        $data = [];
        for ($i = 1; $i <= $docCount; $i++) {
            $data['docId' . $i] = ['foo' => $i];
        }
        $bulkRequest->updateDocuments($this->index, $this->type, $data);

        $operations = $bulkRequest->getOperations();
        $this->assertEquals(false, $bulkRequest->isEmpty());
        $this->assertCount($docCount * 2, $operations);

        for ($i = 1; $i <= $docCount; $i++) {
            $head = current($operations);
            next($operations);
            $data = current($operations);
            next($operations);
            $this->assertArrayHasKey('update', $head);
            $this->assertEquals('docId' . $i, $head['update']['_id']);
            $this->assertEquals($this->index->getName(), $head['update']['_index']);
            $this->assertEquals($this->type->getName(), $head['update']['_type']);
            $this->assertEquals(['doc' => ['foo' => $i]], $data);
        }
    }

    /**
     * Create a new empty bulk request.
     *
     * @return \Smile\ElasticsuiteCore\Index\Bulk\BulkRequest
     */
    private function createBulk()
    {
        return new BulkRequest();
    }
}
