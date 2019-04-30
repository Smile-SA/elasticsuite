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

use Smile\ElasticsuiteCore\Index\Bulk\BulkResponse;

/**
 * Bulk response test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class BulkResponseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BulkResponse
     */
    private $bulkResponse;

    /**
     * Prepare a bulk response to run the test.
     *
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $error1 = ['type' => 'reason1', 'reason' => 'Reason 1'];
        $error2 = ['type' => 'reason2', 'reason' => 'Reason 2'];
        $items = [
            ['index' => ['_index' => 'index', '_type' => 'type', '_id' => 'doc1']],
            ['index' => ['_index' => 'index', '_type' => 'type', '_id' => 'doc2']],
            ['index' => ['_index' => 'index', '_type' => 'type', '_id' => 'doc3', 'error' => $error1]],
            ['index' => ['_index' => 'index', '_type' => 'type', '_id' => 'doc4', 'error' => $error1]],
            ['index' => ['_index' => 'index', '_type' => 'type', '_id' => 'doc5', 'error' => $error2]],
            ['index' => ['_index' => 'index', '_type' => 'type', '_id' => 'doc6', 'error' => $error2]],
        ];
        $this->bulkResponse = new BulkResponse(['errors' => true, 'items'  => $items]);
    }

    /**
     * Test the hasErrors method.
     *
     * @return void
     */
    public function testHasErrors()
    {
        $this->assertEquals(true, $this->bulkResponse->hasErrors());
    }

    /**
     * Test the countSuccess method.
     *
     * @return void
     */
    public function testCountSuccess()
    {
        $this->assertEquals(2, $this->bulkResponse->countSuccess());
    }

    /**
     * Test the countErrors method.
     *
     * @return void
     */
    public function testCountErrors()
    {
        $this->assertEquals(4, $this->bulkResponse->countErrors());
    }

    /**
     * Test the aggregateErrorsByReason method.
     *
     * @return void
     */
    public function testErrorAggregation()
    {
        $aggregatedErrors = $this->bulkResponse->aggregateErrorsByReason();
        $this->assertCount(2, $aggregatedErrors);

        foreach ($aggregatedErrors as $currentError) {
            $this->assertEquals('index', $currentError['index']);
            $this->assertEquals('type', $currentError['document_type']);
            $this->assertEquals('index', $currentError['operation']);
            $this->assertEquals(2, $currentError['count']);
            $this->assertCount(2, $currentError['document_ids']);
        }
    }
}
