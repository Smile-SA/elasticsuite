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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Search\Request\Dimension;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Api\Client\OpaqueIdProviderInterface;
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
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $opaqueIdProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $storeManager;

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
        $this->assertArrayNotHasKey('client', $response['searchResponse']);
    }

    /**
     * Test that the search request X-Opaque-Id contains the container name and the code of the searched store.
     *
     * @return void
     */
    public function testOpaqueId()
    {
        $query    = $this->getMockBuilder(QueryInterface::class)->getMock();
        $scope    = new Dimension('scope', 3);
        $request  = new Request('catalog_view_container', 'indexName', $query, null, null, 0, 10, ['scope' => $scope]);

        $store = $this->getMockBuilder(StoreInterface::class)->getMock();
        $store->method('getCode')->willReturn('de');
        $this->storeManager->method('getStore')->with(3)->willReturn($store);
        $this->opaqueIdProvider->expects($this->once())
            ->method('getOpaqueId')
            ->with(['cnt' => 'catalog_view_container', 'str' => 'de'])
            ->willReturn('area=frontend;str=de;cnt=catalog_view_container');
        $this->client->method('search')->will($this->returnArgument(0));

        $response = $this->adapter->query($request);

        $this->assertEquals(
            ['opaqueId' => 'area=frontend;str=de;cnt=catalog_view_container'],
            $response['searchResponse']['client']
        );
    }

    /**
     * Test that the store is left to the X-Opaque-Id provider when the searched store is unknown or not a single one.
     *
     * @return void
     */
    public function testOpaqueIdWithoutStore()
    {
        $query     = $this->getMockBuilder(QueryInterface::class)->getMock();
        $noScope   = new Request('catalog_view_container', 'indexName', $query);
        $allStores = new Request(
            'catalog_view_container',
            'indexName',
            $query,
            null,
            null,
            0,
            10,
            ['scope' => new Dimension('scope', null)]
        );
        $badScope  = new Request(
            'catalog_view_container',
            'indexName',
            $query,
            null,
            null,
            0,
            10,
            ['scope' => new Dimension('scope', 999)]
        );

        // Only the unknown store id is looked up: StoreManager::getStore(null) would return the current store.
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->with(999)
            ->willThrowException(new NoSuchEntityException());
        $this->opaqueIdProvider->expects($this->exactly(3))
            ->method('getOpaqueId')
            ->with(['cnt' => 'catalog_view_container'])
            ->willReturn('area=frontend;cnt=catalog_view_container');
        $this->client->method('search')->will($this->returnArgument(0));

        $this->adapter->query($noScope);
        $this->adapter->query($allStores);
        $this->adapter->query($badScope);
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

        $this->opaqueIdProvider = $this->getMockBuilder(OpaqueIdProviderInterface::class)->getMock();
        $this->storeManager     = $this->getMockBuilder(StoreManagerInterface::class)->getMock();

        $this->adapter = new Adapter(
            $queryResponseFactory,
            $requestMapper,
            $client,
            $logger,
            $this->opaqueIdProvider,
            $this->storeManager
        );
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
