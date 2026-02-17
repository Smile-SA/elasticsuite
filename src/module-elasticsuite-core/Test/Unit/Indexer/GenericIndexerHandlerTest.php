<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Test\Unit\Indexer;

/**
 * Generic indexer handler test case.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class GenericIndexerHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tests that saveIndex retrieves an existing index without creating a new one.
     *
     * Verifies that when an index already exists, the GenericIndexerHandler retrieves it
     * by name without attempting to create a new index. The test ensures that the index
     * is properly refreshed, installed, and the cache is cleaned after the operation.
     *
     * @return void
     */
    public function testSaveIndexRetrievesExistingIndexWithoutCreating()
    {
        $indexName = 'test_index';
        $typeName = 'test_type';

        $dimensions = [$this->createMock(\Magento\Framework\Search\Request\Dimension::class)];
        $dimensions[0]->expects($this->any())->method('getValue')->willReturn(1);

        $documents = new \ArrayIterator([]);

        $mockIndex = $this->getMockIndex();
        $mockIndexOperation = $this->getMockIndexOperation();
        $mockBatch = $this->getMockBatch();
        $mockCacheHelper = $this->getMockCacheHelper();
        $mockDataSourceResolverFactory = $this->getMockDataSourceResolverFactory();
        $mockIndexSettings = $this->getMockIndexSettings();

        $genericIndexerHandler = new \Smile\ElasticsuiteCore\Indexer\GenericIndexerHandler(
            $mockIndexOperation,
            $mockCacheHelper,
            $mockBatch,
            $mockDataSourceResolverFactory,
            $mockIndexSettings,
            $indexName,
            $typeName
        );

        $mockIndexOperation->expects($this->once())
            ->method('getIndexByName')
            ->with($indexName, 1)
            ->willReturn($mockIndex);

        $mockIndexOperation->expects($this->never())
            ->method('createIndex');

        $mockIndexOperation->expects($this->once())
            ->method('getBatchIndexingSize')
            ->willReturn(100);

        $mockBatch->expects($this->once())
            ->method('getItems')
            ->with($documents, 100)
            ->willReturn(new \ArrayIterator([]));

        $mockIndexOperation->expects($this->once())
            ->method('refreshIndex')
            ->with($mockIndex);

        $mockIndexOperation->expects($this->once())
            ->method('installIndex')
            ->with($mockIndex, 1);

        $mockCacheHelper->expects($this->once())
            ->method('cleanIndexCache')
            ->with($indexName, 1);

        $result = $genericIndexerHandler->saveIndex($dimensions, $documents);

        $this->assertSame($genericIndexerHandler, $result);
    }

    /**
     * Tests that saveIndex creates a new index when getIndexByName throws an exception.
     *
     * Verifies that when retrieving an existing index fails with an exception,
     * the GenericIndexerHandler creates a new index instead. The test ensures that
     * the new index is properly created, refreshed, installed, and the cache is cleaned.
     *
     * @return void
     */
    public function testSaveIndexCreatesNewIndexWhenGetIndexByNameThrowsException()
    {
        $indexName = 'test_index';
        $typeName = 'test_type';

        $dimensions = [$this->createMock(\Magento\Framework\Search\Request\Dimension::class)];
        $dimensions[0]->expects($this->any())->method('getValue')->willReturn(1);

        $documents = new \ArrayIterator([]);

        $mockIndex = $this->getMockIndex();
        $mockIndexOperation = $this->getMockIndexOperation();
        $mockBatch = $this->getMockBatch();
        $mockCacheHelper = $this->getMockCacheHelper();
        $mockDataSourceResolverFactory = $this->getMockDataSourceResolverFactory();
        $mockIndexSettings = $this->getMockIndexSettings();

        $genericIndexerHandler = new \Smile\ElasticsuiteCore\Indexer\GenericIndexerHandler(
            $mockIndexOperation,
            $mockCacheHelper,
            $mockBatch,
            $mockDataSourceResolverFactory,
            $mockIndexSettings,
            $indexName,
            $typeName
        );

        $mockIndexOperation->expects($this->once())
            ->method('getIndexByName')
            ->with($indexName, 1)
            ->willThrowException(new \Exception('Index not found'));

        $mockIndexOperation->expects($this->once())
            ->method('createIndex')
            ->with($indexName, 1)
            ->willReturn($mockIndex);

        $mockIndexOperation->expects($this->once())
            ->method('getBatchIndexingSize')
            ->willReturn(100);

        $mockBatch->expects($this->once())
            ->method('getItems')
            ->with($documents, 100)
            ->willReturn(new \ArrayIterator([]));

        $mockIndexOperation->expects($this->once())
            ->method('refreshIndex')
            ->with($mockIndex);

        $mockIndexOperation->expects($this->once())
            ->method('installIndex')
            ->with($mockIndex, 1);

        $mockCacheHelper->expects($this->once())
            ->method('cleanIndexCache')
            ->with($indexName, 1);

        $result = $genericIndexerHandler->saveIndex($dimensions, $documents);

        $this->assertSame($genericIndexerHandler, $result);
    }

    /**
     * Tests that saveIndex correctly handles multiple document batches.
     *
     * Verifies that when processing a large number of documents that exceed the batch size,
     * the GenericIndexerHandler properly splits them into multiple batches and processes
     * each batch separately. The test ensures that bulk operations are created and executed
     * for each batch, and the index is properly refreshed, installed, and cache is cleaned.
     *
     * @return void
     */
    public function testSaveIndexHandlesMultipleBatchesCorrectly()
    {
        $indexName = 'test_index';
        $typeName = 'test_type';

        $dimensions = [$this->createMock(\Magento\Framework\Search\Request\Dimension::class)];
        $dimensions[0]->expects($this->any())->method('getValue')->willReturn(1);

        $document1 = $this->createMock(\Magento\Framework\Api\Search\DocumentInterface::class);
        $document2 = $this->createMock(\Magento\Framework\Api\Search\DocumentInterface::class);
        $document3 = $this->createMock(\Magento\Framework\Api\Search\DocumentInterface::class);

        $documents = new \ArrayIterator([$document1, $document2, $document3]);

        $mockIndex = $this->getMockIndex();
        $mockIndexOperation = $this->getMockIndexOperation();
        $mockBatch = $this->getMockBatch();
        $mockCacheHelper = $this->getMockCacheHelper();
        $mockDataSourceResolverFactory = $this->getMockDataSourceResolverFactory();
        $mockIndexSettings = $this->getMockIndexSettings();

        $mockDatasource = $this->createMock(\Smile\ElasticsuiteCore\Api\Index\DatasourceInterface::class);
        $mockDatasource->expects($this->exactly(2))
            ->method('addData')
            ->willReturnOnConsecutiveCalls(
                [$document1, $document2],
                [$document3]
            );

        $mockDataSourceResolver = $this->createMock(\Smile\ElasticsuiteCore\Api\Index\DataSourceResolverInterface::class);
        $mockDataSourceResolver->expects($this->exactly(2))
            ->method('getDataSources')
            ->with($indexName)
            ->willReturn([$mockDatasource]);

        $mockDataSourceResolverFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($mockDataSourceResolver);

        $genericIndexerHandler = new \Smile\ElasticsuiteCore\Indexer\GenericIndexerHandler(
            $mockIndexOperation,
            $mockCacheHelper,
            $mockBatch,
            $mockDataSourceResolverFactory,
            $mockIndexSettings,
            $indexName,
            $typeName
        );

        $mockIndexOperation->expects($this->once())
            ->method('getIndexByName')
            ->with($indexName, 1)
            ->willReturn($mockIndex);

        $mockIndexOperation->expects($this->once())
            ->method('getBatchIndexingSize')
            ->willReturn(2);

        $batch1Documents = [$document1, $document2];
        $batch2Documents = [$document3];
        $mockBatch->expects($this->once())
            ->method('getItems')
            ->with($documents, 2)
            ->willReturn([$batch1Documents, $batch2Documents]);

        $mockBulk = $this->createMock(\Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface::class);
        $mockBulk->expects($this->exactly(2))
            ->method('addDocuments')
            ->willReturnSelf();

        $mockIndexOperation->expects($this->exactly(2))
            ->method('createBulk')
            ->willReturn($mockBulk);

        $mockIndexOperation->expects($this->exactly(2))
            ->method('executeBulk')
            ->with($mockBulk);

        $mockIndexOperation->expects($this->once())
            ->method('refreshIndex')
            ->with($mockIndex);

        $mockIndexOperation->expects($this->once())
            ->method('installIndex')
            ->with($mockIndex, 1);

        $mockCacheHelper->expects($this->once())
            ->method('cleanIndexCache')
            ->with($indexName, 1);

        $result = $genericIndexerHandler->saveIndex($dimensions, $documents);

        $this->assertSame($genericIndexerHandler, $result);
    }

    /**
     * Tests that deleteIndex handles the case when index does not exist for a given store ID.
     *
     * Verifies that when an index does not exist for a specific store ID, the GenericIndexerHandler
     * skips the deletion process and does not attempt to retrieve or process documents. The test ensures
     * that index operations are not called when the index does not exist.
     *
     * @return void
     */
    public function testDeleteIndexWhenIndexDoesNotExistForStoreId()
    {
        $indexName = 'test_index';
        $typeName = 'test_type';

        $dimensions = [$this->createMock(\Magento\Framework\Search\Request\Dimension::class)];
        $dimensions[0]->expects($this->any())->method('getValue')->willReturn(1);

        $document1 = $this->createMock(\Magento\Framework\Api\Search\DocumentInterface::class);
        $documents = new \ArrayIterator([$document1]);

        $mockIndexOperation = $this->getMockIndexOperation();
        $mockBatch = $this->getMockBatch();
        $mockCacheHelper = $this->getMockCacheHelper();
        $mockDataSourceResolverFactory = $this->getMockDataSourceResolverFactory();
        $mockIndexSettings = $this->getMockIndexSettings();

        $genericIndexerHandler = new \Smile\ElasticsuiteCore\Indexer\GenericIndexerHandler(
            $mockIndexOperation,
            $mockCacheHelper,
            $mockBatch,
            $mockDataSourceResolverFactory,
            $mockIndexSettings,
            $indexName,
            $typeName
        );

        $mockIndexOperation->expects($this->once())
            ->method('indexExists')
            ->with($indexName, 1)
            ->willReturn(false);

        $mockIndexOperation->expects($this->never())
            ->method('getIndexByName');

        $mockIndexOperation->expects($this->never())
            ->method('getBatchIndexingSize');

        $mockBatch->expects($this->never())
            ->method('getItems');

        $mockIndexOperation->expects($this->never())
            ->method('createBulk');

        $mockIndexOperation->expects($this->never())
            ->method('executeBulk');

        $mockIndexOperation->expects($this->never())
            ->method('refreshIndex');

        $result = $genericIndexerHandler->deleteIndex($dimensions, $documents);

        $this->assertSame($genericIndexerHandler, $result);
    }

    /**
     * Tests that deleteIndex processes multiple dimensions with different store IDs correctly.
     *
     * Verifies that when deleteIndex is called with multiple dimensions each having different store IDs,
     * the GenericIndexerHandler correctly retrieves the index for each store ID, processes the documents
     * in batches, executes bulk delete operations for each batch, and refreshes the index for each store.
     * The test ensures that all store-specific operations are performed independently.
     *
     * @return void
     */
    public function testDeleteIndexWithMultipleDimensionsEachHavingDifferentStoreIds()
    {
        $indexName = 'test_index';
        $typeName = 'test_type';

        $dimension1 = $this->createMock(\Magento\Framework\Search\Request\Dimension::class);
        $dimension1->expects($this->any())->method('getValue')->willReturn(1);

        $dimension2 = $this->createMock(\Magento\Framework\Search\Request\Dimension::class);
        $dimension2->expects($this->any())->method('getValue')->willReturn(2);

        $document1 = $this->createMock(\Magento\Framework\Api\Search\DocumentInterface::class);
        $document2 = $this->createMock(\Magento\Framework\Api\Search\DocumentInterface::class);

        $documents = new \ArrayIterator([$document1, $document2]);

        $mockIndexOperation = $this->getMockIndexOperation();
        $mockBatch = $this->getMockBatch();
        $mockCacheHelper = $this->getMockCacheHelper();
        $mockDataSourceResolverFactory = $this->getMockDataSourceResolverFactory();
        $mockIndexSettings = $this->getMockIndexSettings();

        $mockIndex1 = $this->getMockIndex();
        $mockIndex2 = $this->getMockIndex();

        $genericIndexerHandler = new \Smile\ElasticsuiteCore\Indexer\GenericIndexerHandler(
            $mockIndexOperation,
            $mockCacheHelper,
            $mockBatch,
            $mockDataSourceResolverFactory,
            $mockIndexSettings,
            $indexName,
            $typeName
        );

        $invokeCount = $this->exactly(2);
        $invocationsCountCallback = 'numberOfInvocations';
        if (method_exists($invokeCount, 'getInvocationCount')) {
            // Method 'numberOfInvocations' only exists starting from PHPUnit 10.
            $invocationsCountCallback = 'getInvocationCount';
        }
        $mockIndexOperation->expects($invokeCount)
            ->method('indexExists')
            /*
             * withConsecutive removed in PHPUnit 10 without any alternative \o/.
             * ---
             * ->withConsecutive(
             *   [$indexName, 1],
             *   [$indexName, 2]
             * )
             * ->willReturn(true);
             */
            ->willReturnCallback(function (...$expectedParameters) use ($invokeCount, $invocationsCountCallback, $indexName) {
                $this->assertEquals($indexName, $expectedParameters[0]);
                if ($invokeCount->$invocationsCountCallback() === 1) {
                    $this->assertEquals(1, $expectedParameters[1]);
                }
                if ($invokeCount->$invocationsCountCallback() === 2) {
                    $this->assertEquals(2, $expectedParameters[1]);
                }

                return true;
            });

        $invokeCount = $this->exactly(2);
        $mockIndexOperation->expects($invokeCount)
            ->method('getIndexByName')
            /*
            ->withConsecutive(
                [$indexName, 1],
                [$indexName, 2]
            )
            ->willReturnOnConsecutiveCalls($mockIndex1, $mockIndex2);
            */
            ->willReturnCallback(
                function (...$expectedParameters) use ($invokeCount, $invocationsCountCallback, $indexName, $mockIndex1, $mockIndex2) {
                    $this->assertEquals($indexName, $expectedParameters[0]);
                    if ($invokeCount->$invocationsCountCallback() === 1) {
                        $this->assertEquals(1, $expectedParameters[1]);
                    }
                    if ($invokeCount->$invocationsCountCallback() === 2) {
                        $this->assertEquals(2, $expectedParameters[1]);
                    }

                    return ($expectedParameters[1] === 1) ? $mockIndex1 : $mockIndex2;
                }
            );

        $mockIndexOperation->expects($this->exactly(2))
            ->method('getBatchIndexingSize')
            ->willReturn(100);

        $mockBatch->expects($this->exactly(2))
            ->method('getItems')
            ->with($documents, 100)
            ->willReturn(new \ArrayIterator([[$document1, $document2]]));

        $mockBulk1 = $this->createMock(\Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface::class);
        $mockBulk1->expects($this->once())
            ->method('deleteDocuments')
            ->with($mockIndex1, [$document1, $document2])
            ->willReturnSelf();

        $mockBulk2 = $this->createMock(\Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface::class);
        $mockBulk2->expects($this->once())
            ->method('deleteDocuments')
            ->with($mockIndex2, [$document1, $document2])
            ->willReturnSelf();

        $mockIndexOperation->expects($this->exactly(2))
            ->method('createBulk')
            ->willReturnOnConsecutiveCalls($mockBulk1, $mockBulk2);

        $invokeCount = $this->exactly(2);
        $mockIndexOperation->expects($invokeCount)
            ->method('executeBulk')
            // ->withConsecutive([$mockBulk1], [$mockBulk2]);
            ->willReturnCallback(function (...$expectedParameters) use ($invokeCount, $invocationsCountCallback, $mockBulk1, $mockBulk2) {
                if ($invokeCount->$invocationsCountCallback() === 1) {
                    $this->assertEquals($mockBulk1, $expectedParameters[0]);
                }
                if ($invokeCount->$invocationsCountCallback() === 2) {
                    $this->assertEquals($mockBulk2, $expectedParameters[0]);
                }

                return true;
            });


        $invokeCount = $this->exactly(2);
        $mockIndexOperation->expects($invokeCount)
            ->method('refreshIndex')
            // ->withConsecutive([$mockIndex1], [$mockIndex2]);
            ->willReturnCallback(function (...$expectedParameters) use ($invokeCount, $invocationsCountCallback, $mockIndex1, $mockIndex2) {
                if ($invokeCount->$invocationsCountCallback() === 1) {
                    $this->assertEquals($mockIndex1, $expectedParameters[0]);
                }
                if ($invokeCount->$invocationsCountCallback() === 2) {
                    $this->assertEquals($mockIndex2, $expectedParameters[0]);
                }

                return true;
            });


        $result = $genericIndexerHandler->deleteIndex([$dimension1, $dimension2], $documents);

        $this->assertSame($genericIndexerHandler, $result);
    }

    /**
     * Tests that deleteIndex calls refreshIndex exactly once after processing all batches.
     *
     * Verifies that when deleteIndex is called with multiple batches of documents,
     * the GenericIndexerHandler processes each batch separately but calls refreshIndex
     * only once after all batches have been processed. The test ensures that refreshIndex
     * is not called multiple times during batch processing.
     *
     * @return void
     */
    public function testDeleteIndexCallsRefreshIndexExactlyOnceAfterProcessingAllBatches()
    {
        $indexName = 'test_index';
        $typeName = 'test_type';

        $dimension = $this->createMock(\Magento\Framework\Search\Request\Dimension::class);
        $dimension->expects($this->any())->method('getValue')->willReturn(1);

        $document1 = $this->createMock(\Magento\Framework\Api\Search\DocumentInterface::class);
        $document2 = $this->createMock(\Magento\Framework\Api\Search\DocumentInterface::class);
        $document3 = $this->createMock(\Magento\Framework\Api\Search\DocumentInterface::class);

        $documents = new \ArrayIterator([$document1, $document2, $document3]);

        $mockIndex = $this->getMockIndex();
        $mockIndexOperation = $this->getMockIndexOperation();
        $mockBatch = $this->getMockBatch();
        $mockCacheHelper = $this->getMockCacheHelper();
        $mockDataSourceResolverFactory = $this->getMockDataSourceResolverFactory();
        $mockIndexSettings = $this->getMockIndexSettings();

        $genericIndexerHandler = new \Smile\ElasticsuiteCore\Indexer\GenericIndexerHandler(
            $mockIndexOperation,
            $mockCacheHelper,
            $mockBatch,
            $mockDataSourceResolverFactory,
            $mockIndexSettings,
            $indexName,
            $typeName
        );

        $mockIndexOperation->expects($this->once())
            ->method('indexExists')
            ->with($indexName, 1)
            ->willReturn(true);

        $mockIndexOperation->expects($this->once())
            ->method('getIndexByName')
            ->with($indexName, 1)
            ->willReturn($mockIndex);

        $mockIndexOperation->expects($this->once())
            ->method('getBatchIndexingSize')
            ->willReturn(1);

        $batch1Documents = [$document1];
        $batch2Documents = [$document2];
        $batch3Documents = [$document3];
        $mockBatch->expects($this->once())
            ->method('getItems')
            ->with($documents, 1)
            ->willReturn([$batch1Documents, $batch2Documents, $batch3Documents]);

        $mockBulk = $this->createMock(\Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface::class);
        $mockBulk->expects($this->exactly(3))
            ->method('deleteDocuments')
            ->with($mockIndex)
            ->willReturnSelf();

        $mockIndexOperation->expects($this->exactly(3))
            ->method('createBulk')
            ->willReturn($mockBulk);

        $mockIndexOperation->expects($this->exactly(3))
            ->method('executeBulk')
            ->with($mockBulk);

        $mockIndexOperation->expects($this->once())
            ->method('refreshIndex')
            ->with($mockIndex);

        $result = $genericIndexerHandler->deleteIndex([$dimension], $documents);

        $this->assertSame($genericIndexerHandler, $result);
    }

    /**
     * Tests that deleteIndex skips processing for a specific dimension if index does not exist
     * while processing other dimensions.
     *
     * Verifies that when deleteIndex is called with multiple dimensions and the index does not exist
     * for one specific dimension (store ID), the GenericIndexerHandler skips the deletion process for that
     * dimension but continues processing other dimensions where the index exists. The test ensures that
     * bulk operations and refresh are only performed for dimensions where the index exists.
     *
     * @return void
     */
    public function testDeleteIndexSkipsProcessingForSpecificDimensionIfIndexDoesNotExist()
    {
        $indexName = 'test_index';
        $typeName = 'test_type';

        $dimension1 = $this->createMock(\Magento\Framework\Search\Request\Dimension::class);
        $dimension1->expects($this->any())->method('getValue')->willReturn(1);

        $dimension2 = $this->createMock(\Magento\Framework\Search\Request\Dimension::class);
        $dimension2->expects($this->any())->method('getValue')->willReturn(2);

        $dimension3 = $this->createMock(\Magento\Framework\Search\Request\Dimension::class);
        $dimension3->expects($this->any())->method('getValue')->willReturn(3);

        $document1 = $this->createMock(\Magento\Framework\Api\Search\DocumentInterface::class);
        $document2 = $this->createMock(\Magento\Framework\Api\Search\DocumentInterface::class);

        $documents = new \ArrayIterator([$document1, $document2]);

        $mockIndexOperation = $this->getMockIndexOperation();
        $mockBatch = $this->getMockBatch();
        $mockCacheHelper = $this->getMockCacheHelper();
        $mockDataSourceResolverFactory = $this->getMockDataSourceResolverFactory();
        $mockIndexSettings = $this->getMockIndexSettings();

        $mockIndex1 = $this->getMockIndex();
        $mockIndex3 = $this->getMockIndex();

        $genericIndexerHandler = new \Smile\ElasticsuiteCore\Indexer\GenericIndexerHandler(
            $mockIndexOperation,
            $mockCacheHelper,
            $mockBatch,
            $mockDataSourceResolverFactory,
            $mockIndexSettings,
            $indexName,
            $typeName
        );

        $invokeCount = $this->exactly(3);
        $invocationsCountCallback = 'numberOfInvocations';
        if (method_exists($invokeCount, 'getInvocationCount')) {
            // Method 'numberOfInvocations' only exists starting from PHPUnit 10.
            $invocationsCountCallback = 'getInvocationCount';
        }
        $mockIndexOperation->expects($invokeCount)
            ->method('indexExists')
            /*
            ->withConsecutive(
                [$indexName, 1],
                [$indexName, 2],
                [$indexName, 3]
            )
            ->willReturnOnConsecutiveCalls(true, false, true);
            */
            ->willReturnCallback(function (...$expectedParameters) use ($invokeCount, $invocationsCountCallback, $indexName) {
                $this->assertEquals($indexName, $expectedParameters[0], 'Index name');
                if ($invokeCount->$invocationsCountCallback() === 1) {
                    $this->assertEquals(1, $expectedParameters[1], 'Store ID');

                    return true;
                }
                if ($invokeCount->$invocationsCountCallback() === 2) {
                    $this->assertEquals(2, $expectedParameters[1], 'Store ID');

                    return false;
                }
                if ($invokeCount->$invocationsCountCallback() === 3) {
                    $this->assertEquals(3, $expectedParameters[1], 'Store ID');

                    return true;
                }

                return false;
            });

        $invokeCount = $this->exactly(2);
        $mockIndexOperation->expects($invokeCount)
            ->method('getIndexByName')
            /*
            ->withConsecutive(
                [$indexName, 1],
                [$indexName, 3]
            )
            ->willReturnOnConsecutiveCalls($mockIndex1, $mockIndex3);
            */
            ->willReturnCallback(
                function (...$expectedParameters) use (
                    $invokeCount,
                    $invocationsCountCallback,
                    $indexName,
                    $mockIndex1,
                    $mockIndex3
                ) {
                    $this->assertEquals($indexName, $expectedParameters[0], 'Index name');
                    if ($invokeCount->$invocationsCountCallback() === 1) {
                        $this->assertEquals(1, $expectedParameters[1], 'Store ID');

                        return $mockIndex1;
                    }
                    if ($invokeCount->$invocationsCountCallback() === 2) {
                        $this->assertEquals(3, $expectedParameters[1], 'Store ID');

                        return $mockIndex3;
                    }

                    return null;
                }
            );

        $mockIndexOperation->expects($this->exactly(2))
            ->method('getBatchIndexingSize')
            ->willReturn(100);

        $mockBatch->expects($this->exactly(2))
            ->method('getItems')
            ->with($documents, 100)
            ->willReturn(new \ArrayIterator([[$document1, $document2]]));

        $mockBulk1 = $this->createMock(\Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface::class);
        $mockBulk1->expects($this->once())
            ->method('deleteDocuments')
            ->with($mockIndex1, [$document1, $document2])
            ->willReturnSelf();

        $mockBulk3 = $this->createMock(\Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface::class);
        $mockBulk3->expects($this->once())
            ->method('deleteDocuments')
            ->with($mockIndex3, [$document1, $document2])
            ->willReturnSelf();

        $mockIndexOperation->expects($this->exactly(2))
            ->method('createBulk')
            ->willReturnOnConsecutiveCalls($mockBulk1, $mockBulk3);

        $invokeCount = $this->exactly(2);
        $mockIndexOperation->expects($invokeCount)
            ->method('executeBulk')
            // ->withConsecutive([$mockBulk1], [$mockBulk3]);
            ->willReturnCallback(function (...$expectedParameters) use ($invokeCount, $invocationsCountCallback, $mockBulk1, $mockBulk3) {
                if ($invokeCount->$invocationsCountCallback() === 1) {
                    $this->assertEquals($mockBulk1, $expectedParameters[0]);

                    return true;
                }
                if ($invokeCount->$invocationsCountCallback() === 2) {
                    $this->assertEquals($mockBulk3, $expectedParameters[0]);

                    return true;
                }

                return false;
            });

        /*
        $mockIndexOperation->expects($this->once())
            ->method('refreshIndex')
            ->with($mockIndex1);

        $result = $genericIndexerHandler->deleteIndex([$dimension1, $dimension2, $dimension3], $documents);

        $this->assertSame($genericIndexerHandler, $result);
        */
        $invokeCount = $this->exactly(2);
        $mockIndexOperation->expects($invokeCount)
            ->method('refreshIndex')
            // ->withConsecutive([$mockIndex1], [$mockIndex3]);
            ->willReturnCallback(function (...$expectedParameters) use ($invokeCount, $invocationsCountCallback, $mockIndex1, $mockIndex3) {
                if ($invokeCount->$invocationsCountCallback() === 1) {
                    $this->assertEquals($mockIndex1, $expectedParameters[0]);

                    return true;
                }
                if ($invokeCount->$invocationsCountCallback() === 2) {
                    $this->assertEquals($mockIndex3, $expectedParameters[0]);

                    return true;
                }

                return false;
            });

        $result = $genericIndexerHandler->deleteIndex([$dimension1, $dimension2, $dimension3], $documents);

        $this->assertSame($genericIndexerHandler, $result);
    }

    /**
     * Tests that cleanIndex calls createIndex with correct indexName and dimension values.
     *
     * Verifies that when cleanIndex is called with multiple dimensions, the GenericIndexerHandler
     * calls createIndex exactly once for each dimension, passing the correct indexName and the
     * value retrieved from each dimension. The test ensures that createIndex is invoked with the
     * proper parameters for each dimension independently.
     *
     * @return void
     */
    public function testCleanIndexCallsCreateIndexWithCorrectIndexNameAndDimensionValues()
    {
        $indexName = 'test_index';
        $typeName = 'test_type';

        $dimension1 = $this->createMock(\Magento\Framework\Search\Request\Dimension::class);
        $dimension1->expects($this->any())->method('getValue')->willReturn(1);

        $dimension2 = $this->createMock(\Magento\Framework\Search\Request\Dimension::class);
        $dimension2->expects($this->any())->method('getValue')->willReturn(2);

        $dimension3 = $this->createMock(\Magento\Framework\Search\Request\Dimension::class);
        $dimension3->expects($this->any())->method('getValue')->willReturn(3);

        $mockIndexOperation = $this->getMockIndexOperation();
        $mockBatch = $this->getMockBatch();
        $mockCacheHelper = $this->getMockCacheHelper();
        $mockDataSourceResolverFactory = $this->getMockDataSourceResolverFactory();
        $mockIndexSettings = $this->getMockIndexSettings();

        $genericIndexerHandler = new \Smile\ElasticsuiteCore\Indexer\GenericIndexerHandler(
            $mockIndexOperation,
            $mockCacheHelper,
            $mockBatch,
            $mockDataSourceResolverFactory,
            $mockIndexSettings,
            $indexName,
            $typeName
        );

        $mockIndex1 = $this->getMockIndex();
        $mockIndex2 = $this->getMockIndex();
        $mockIndex3 = $this->getMockIndex();

        $invokeCount = $this->exactly(3);
        $invocationsCountCallback = 'numberOfInvocations';
        if (method_exists($invokeCount, 'getInvocationCount')) {
            // Method 'numberOfInvocations' only exists starting from PHPUnit 10.
            $invocationsCountCallback = 'getInvocationCount';
        }
        $mockIndexOperation->expects($invokeCount)
            ->method('createIndex')
            /*
            ->withConsecutive(
                [$indexName, 1],
                [$indexName, 2],
                [$indexName, 3]
            )
            ->willReturnOnConsecutiveCalls($mockIndex1, $mockIndex2, $mockIndex3);
            */
            ->willReturnCallback(
                function (...$expectedParameters) use (
                    $invokeCount,
                    $invocationsCountCallback,
                    $indexName,
                    $mockIndex1,
                    $mockIndex2,
                    $mockIndex3
                ) {
                    $this->assertEquals($indexName, $expectedParameters[0], 'Index name');
                    if ($invokeCount->$invocationsCountCallback() === 1) {
                        $this->assertEquals(1, $expectedParameters[1], 'Store ID');

                        return $mockIndex1;
                    }
                    if ($invokeCount->$invocationsCountCallback() === 2) {
                        $this->assertEquals(2, $expectedParameters[1], 'Store ID');

                        return $mockIndex2;
                    }
                    if ($invokeCount->$invocationsCountCallback() === 3) {
                        $this->assertEquals(3, $expectedParameters[1], 'Store ID');

                        return $mockIndex3;
                    }

                    return null;
                }
            );

        $result = $genericIndexerHandler->cleanIndex([$dimension1, $dimension2, $dimension3]);

        $this->assertSame($genericIndexerHandler, $result);
    }

    /**
     * Tests that cleanIndex throws an exception when indexOperation createIndex fails.
     *
     * Verifies that when createIndex throws an exception during the cleanIndex operation,
     * the GenericIndexerHandler propagates the exception instead of catching or suppressing it.
     * The test ensures that the exception is raised to the caller.
     *
     * @return void
     */
    public function testCleanIndexThrowsExceptionWhenCreateIndexFails()
    {
        $indexName = 'test_index';
        $typeName = 'test_type';

        $dimension1 = $this->createMock(\Magento\Framework\Search\Request\Dimension::class);
        $dimension1->expects($this->any())->method('getValue')->willReturn(1);

        $dimension2 = $this->createMock(\Magento\Framework\Search\Request\Dimension::class);
        $dimension2->expects($this->any())->method('getValue')->willReturn(2);

        $mockIndexOperation = $this->getMockIndexOperation();
        $mockBatch = $this->getMockBatch();
        $mockCacheHelper = $this->getMockCacheHelper();
        $mockDataSourceResolverFactory = $this->getMockDataSourceResolverFactory();
        $mockIndexSettings = $this->getMockIndexSettings();

        $genericIndexerHandler = new \Smile\ElasticsuiteCore\Indexer\GenericIndexerHandler(
            $mockIndexOperation,
            $mockCacheHelper,
            $mockBatch,
            $mockDataSourceResolverFactory,
            $mockIndexSettings,
            $indexName,
            $typeName
        );

        $mockIndex1 = $this->getMockIndex();
        $invokeCount = $this->exactly(2);
        $invocationsCountCallback = 'numberOfInvocations';
        if (method_exists($invokeCount, 'getInvocationCount')) {
            // Method 'numberOfInvocations' only exists starting from PHPUnit 10.
            $invocationsCountCallback = 'getInvocationCount';
        }
        $mockIndexOperation->expects($invokeCount)
            ->method('createIndex')
            /*
            ->withConsecutive(
                [$indexName, 1],
                [$indexName, 2]
            )
            ->willReturnOnConsecutiveCalls(
                $mockIndex1,
                $this->throwException(new \Exception('Failed to create index'))
            );
            */
            ->willReturnCallback(
                function (...$expectedParameters) use (
                    $invokeCount,
                    $invocationsCountCallback,
                    $indexName,
                    $mockIndex1
                ) {
                    $this->assertEquals($indexName, $expectedParameters[0], 'Index name');
                    if ($invokeCount->$invocationsCountCallback() === 1) {
                        $this->assertEquals(1, $expectedParameters[1], 'Store ID');

                        return $mockIndex1;
                    }
                    if ($invokeCount->$invocationsCountCallback() === 2) {
                        $this->assertEquals(2, $expectedParameters[1], 'Store ID');
                        throw new \Exception('Failed to create index');
                    }

                    return null;
                }
            );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create index');

        $genericIndexerHandler->cleanIndex([$dimension1, $dimension2]);
    }

    /**
     * Tests that isAvailable ignores the dimensions parameter and delegates directly to indexOperation.isAvailable().
     *
     * Verifies that when isAvailable is called with dimensions, the GenericIndexerHandler
     * ignores the dimensions parameter and calls indexOperation.isAvailable() without passing
     * any arguments. The test ensures that the result from indexOperation.isAvailable() is
     * returned directly without modification.
     *
     * @return void
     */
    public function testIsAvailableIgnoresDimensionsParameterAndDelegatesDirectly()
    {
        $indexName = 'test_index';
        $typeName = 'test_type';

        $dimension1 = $this->createMock(\Magento\Framework\Search\Request\Dimension::class);
        $dimension1->expects($this->any())->method('getValue')->willReturn(1);

        $dimension2 = $this->createMock(\Magento\Framework\Search\Request\Dimension::class);
        $dimension2->expects($this->any())->method('getValue')->willReturn(2);

        $mockIndexOperation = $this->getMockIndexOperation();
        $mockBatch = $this->getMockBatch();
        $mockCacheHelper = $this->getMockCacheHelper();
        $mockDataSourceResolverFactory = $this->getMockDataSourceResolverFactory();
        $mockIndexSettings = $this->getMockIndexSettings();

        $genericIndexerHandler = new \Smile\ElasticsuiteCore\Indexer\GenericIndexerHandler(
            $mockIndexOperation,
            $mockCacheHelper,
            $mockBatch,
            $mockDataSourceResolverFactory,
            $mockIndexSettings,
            $indexName,
            $typeName
        );

        $mockIndexOperation->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $result = $genericIndexerHandler->isAvailable([$dimension1, $dimension2]);

        $this->assertTrue($result);
    }

    /**
     * Return mock Index instance.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Smile\ElasticsuiteCore\Api\Index\IndexInterface
     */
    protected function getMockIndex()
    {
        return $this->createMock(\Smile\ElasticsuiteCore\Api\Index\IndexInterface::class);
    }

    /**
     * Return mock IndexOperation instance.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface
     */
    protected function getMockIndexOperation()
    {
        return $this->createMock(\Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface::class);
    }

    /**
     * Return mock Batch instance.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Magento\Framework\Indexer\SaveHandler\Batch
     */
    protected function getMockBatch()
    {
        return $this->createMock(\Magento\Framework\Indexer\SaveHandler\Batch::class);
    }

    /**
     * Return mock Cache helper instance.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Smile\ElasticsuiteCore\Helper\Cache
     */
    protected function getMockCacheHelper()
    {
        return $this->createMock(\Smile\ElasticsuiteCore\Helper\Cache::class);
    }

    /**
     * Return mock Datasource resolver factory instance.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Smile\ElasticsuiteCore\Api\Index\DataSourceResolverInterfaceFactory
     */
    protected function getMockDataSourceResolverFactory()
    {
        return $this->createMock(\Smile\ElasticsuiteCore\Api\Index\DataSourceResolverInterfaceFactory::class);
    }

    /**
     * Return mock IndexSettings instance.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface
     */
    protected function getMockIndexSettings()
    {
        return $this->createMock(\Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface::class);
    }
}
