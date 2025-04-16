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
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Adapter;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Mapper;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCore\Search\Request;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * ES Search adapter test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AdapterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Smile\ElasticsuiteCore\Api\Client\ClientInterface
     */
    private $client;

    /**
     *
     * @var \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Adapter
     */
    private $adapter;

    /**
     * Test the search response when the ES query is succesful.
     *
     * @return void
     */
    public function testSuccess()
    {
        $query    = $this->getMockBuilder(QueryInterface::class)->getMock();
        $request  = new Request('requestName', 'indexName', $query);

        $this->client->method('search')->will($this->returnArgument(0));

        $response = $this->adapter->query($request);

        $this->assertEquals('indexName', $response['searchResponse']['index']);
        $this->assertEquals('searchQueryBody', $response['searchResponse']['body']);
    }

    /**
     * Test the search response when the ES query throws an exception.
     *
     * @return void
     */
    public function testError()
    {
        $query    = $this->getMockBuilder(QueryInterface::class)->getMock();
        $request  = new Request('requestName', 'indexName', $query);

        $this->client->method('search')->willThrowException(new \Exception('Search exception'));

        $response = $this->adapter->query($request);

        $this->assertEmpty($response['searchResponse']);
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $queryResponseFactory = $this->getQueryResponseFactoryMock();
        $requestMapper        = $this->getRequestMapperMock();
        $client               = $this->getClientMock();
        $logger               = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->adapter = new Adapter($queryResponseFactory, $requestMapper, $client, $logger);
    }

    /**
     * Init a mocked search request mapper.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getRequestMapperMock()
    {
        $mapperMock = $this->getMockBuilder(Mapper::class)->disableOriginalConstructor()->getMock();

        $mapperMock->method('buildSearchRequest')->will($this->returnValue('searchQueryBody'));

        return $mapperMock;
    }

    /**
     * Init a mocked search response factory.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getQueryResponseFactoryMock()
    {
        $queryResponseFactoryMock = $this->getMockBuilder(QueryResponse::class . 'Factory')
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $queryResponseFactoryMock->method('create')->will($this->returnArgument(0));

        return $queryResponseFactoryMock;
    }

    /**
     * Init a mocked ES client factory.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getClientMock()
    {
        $this->client = $this->getMockBuilder(ClientInterface::class)->disableOriginalConstructor()->getMock();

        return $this->client;
    }
}
