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

use Smile\ElasticsuiteCore\Api\Index\Ingest\PipelineManagerInterface;
use Smile\ElasticsuiteCore\Index\IndexOperation;

/**
 * Index operation test case.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class IndexOperationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Smile\ElasticsuiteCore\Index\IndexOperation
     */
    private $indexOperation;

    /**
     * @var \OpenSearch\Client|\\PHPUnit\Framework\MockObject\MockObject
     */
    private $clientMock;

    /**
     * @var array
     */
    private $logRows = [];

    /**
     * Init mocks used by the test case.
     *
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->initClientMock();

        $objectManager = $this->getObjectManagerMock();
        $indexSettings = $this->getIndexSettingsMock();
        $pipelineManager = $this->getMockBuilder(PipelineManagerInterface::class)
                                ->disableOriginalConstructor()
                                ->getMock();
        $logger        = $this->getLoggerMock();

        $this->indexOperation = new IndexOperation($objectManager, $this->clientMock, $indexSettings, $pipelineManager, $logger);
    }

    /**
     * Test the is available method returns true if client ping is OK.
     *
     * @return void
     */
    public function testIsAvailable()
    {
        $this->clientMock->method('ping')->will($this->returnValue(true));
        $this->assertEquals(true, $this->indexOperation->isAvailable());
    }

    /**
     * Test the is available method returns false if client ping raised an exception.
     *
     * @return void
     */
    public function testIsNotAvailable()
    {
        $this->clientMock->method('ping')->willThrowException(new \Exception());
        $this->assertEquals(false, $this->indexOperation->isAvailable());
    }

    /**
     * Test index getter by identifier / store code.
     *
     * @return void
     */
    public function testGetIndexByName()
    {
        $index = $this->indexOperation->getIndexByName('index_identifier', 'store_code');
        $this->assertInstanceOf(\Smile\ElasticsuiteCore\Api\Index\IndexInterface::class, $index);
    }

    /**
     * Test accessing a not existing index throws an exception.
     *
     * @return void
     */
    public function testGetIndexInvalidByName()
    {
        $this->expectExceptionMessage("invalid_index_identifier index does not exist yet. Make sure everything is reindexed.");
        $this->expectException(\LogicException::class);
        $index = $this->indexOperation->getIndexByName('invalid_index_identifier', 'store_code');
        $this->assertInstanceOf(\Smile\ElasticsuiteCore\Api\Index\IndexInterface::class, $index);
    }

    /**
     * Bulk creation test.
     *
     * @return void
     */
    public function testCreateBulk()
    {
        $bulk = $this->indexOperation->createBulk();
        $this->assertInstanceOf(\Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface::class, $bulk);
    }

    /**
     * Bulk execution test.
     *
     * @return void
     */
    public function testExecuteBulk()
    {
        $this->clientMock->method('bulk')->will($this->returnValue([
            'errors' => true,
            'items'  => [
                ['index' => ['_index' => 'index', '_id' => 'doc1']],
                ['index' => ['_index' => 'index', '_id' => 'doc2']],
            ],
        ]));

        $bulkMock = $this->indexOperation->createBulk();
        $bulkMock->method('getOperations')->will($this->returnValue([]));

        $this->indexOperation->executeBulk($bulkMock);

        $this->assertArrayNotHasKey('errors', $this->logRows);
    }

    /**
     * Bulk execution test with error logging.
     *
     * @return void
     */
    public function testExecuteBulkWithErrors()
    {
        $error1 = ['type' => 'reason1', 'reason' => 'Reason 1'];
        $error2 = ['type' => 'reason2', 'reason' => 'Reason 2'];
        $this->clientMock->method('bulk')->will($this->returnValue([
            'errors' => true,
            'items'  => [
                ['index' => ['_index' => 'index', '_id' => 'doc1']],
                ['index' => ['_index' => 'index', '_id' => 'doc2']],
                ['index' => ['_index' => 'index', '_id' => 'doc3', 'error' => $error1]],
                ['index' => ['_index' => 'index', '_id' => 'doc4', 'error' => $error1]],
                ['index' => ['_index' => 'index', '_id' => 'doc5', 'error' => $error2]],
                ['index' => ['_index' => 'index', '_id' => 'doc6', 'error' => $error2]],
            ],
        ]));

        $bulkMock = $this->indexOperation->createBulk();
        $bulkMock->method('getOperations')->will($this->returnValue([]));

        $this->indexOperation->executeBulk($bulkMock);

        $this->assertArrayHasKey('errors', $this->logRows);
        $this->assertCount(2, $this->logRows['errors']);
        $errMessages = [
            'Bulk index operation failed 2 times in index index.',
            'Error (reason2) : Reason 2.',
            'Failed doc ids sample : doc5, doc6.',
        ];

        $this->assertEquals(implode(' ', $errMessages), end($this->logRows['errors']));
    }

    /**
     * Test empty bulk execution throws an exception.
     *
     * @return void
     */
    public function testExecuteEmptyBulk()
    {
        $this->expectExceptionMessage("Can not execute empty bulk.");
        $this->expectException(\LogicException::class);
        $bulk = new \Smile\ElasticsuiteCore\Index\Bulk\BulkRequest();
        $this->indexOperation->executeBulk($bulk);
    }

    /**
     * Test getting batch indexing size.
     *
     * @return void
     */
    public function testGetBatchIndexingSize()
    {
        $this->assertEquals(100, $this->indexOperation->getBatchIndexingSize());
    }

    /**
     * Object manager mocking.
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getObjectManagerMock()
    {
        $objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $createObjectStub = function ($className, $args) {
            $instance = null;

            if ($className === 'Smile\ElasticsuiteCore\Api\Index\Bulk\BulkResponseInterface') {
                $reflect = new \ReflectionClass(\Smile\ElasticsuiteCore\Index\Bulk\BulkResponse::class);
                $instance = $reflect->newInstanceArgs($args);
            }

            if ($instance === null) {
                $mockBuilder = $this->getMockBuilder($className);
                $mockBuilder->disableOriginalConstructor();
                $instance = $mockBuilder->getMock();
            }

            return $instance;
        };

        $objectManagerMock->method('create')->will($this->returnCallback($createObjectStub));

        return $objectManagerMock;
    }

    /**
     * Client factory mocking.
     *
     * @return void
     */
    private function initClientMock()
    {
        $this->clientMock = $this->getMockBuilder(\Smile\ElasticsuiteCore\Api\Client\ClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $indicesExistsMethodStub = function ($indexName) {
            return $indexName === 'index_identifier_store_code';
        };
        $this->clientMock->method('indexExists')->will($this->returnCallback($indicesExistsMethodStub));
    }

    /**
     * Index settings mocking.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getIndexSettingsMock()
    {
        $indexSettingsMock = $this->getMockBuilder(\Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface::class)->getMock();
        $getIndexIdentiferMethodStub = function ($indexIdentifier, $store) {
            return "{$indexIdentifier}_{$store}";
        };

        $indexSettingsMock->method('getIndexAliasFromIdentifier')->will($this->returnCallback($getIndexIdentiferMethodStub));
        $indexSettingsMock->method('getIndicesConfig')->will($this->returnValue(['index_identifier' => []]));
        $indexSettingsMock->method('getBatchIndexingSize')->will($this->returnValue(100));

        return $indexSettingsMock;
    }

    /**
     * Logger mocking.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getLoggerMock()
    {
        $loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)->getMock();

        $errorLoggerStub = function ($message, $context = '') {

            if ($context !== '') {
                $this->logRows['context']['errors'][] = $message;
            }

            $this->logRows['errors'][] = $message;
        };
        $loggerMock->method('error')->will($this->returnCallback($errorLoggerStub));

        return $loggerMock;
    }
}
