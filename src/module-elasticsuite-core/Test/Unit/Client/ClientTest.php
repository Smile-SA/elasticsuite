<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Test\Unit\Client;

use OpenSearch\Client as EsClient;
use OpenSearch\Namespaces\IndicesNamespace;
use OpenSearch\Namespaces\IngestNamespace;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Client\OpaqueIdProviderInterface;
use Smile\ElasticsuiteCore\Client\Client;
use Smile\ElasticsuiteCore\Client\ClientBuilder;

/**
 * Client X-Opaque-Id unit tests.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class ClientTest extends TestCase
{
    /**
     * @var string
     */
    const OPAQUE_ID = 'area=adminhtml;cli=indexer:reindex';

    /**
     * @var EsClient|MockObject
     */
    private $esClient;

    /**
     * @var OpaqueIdProviderInterface|MockObject
     */
    private $opaqueIdProvider;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->esClient         = $this->createMock(EsClient::class);
        $this->opaqueIdProvider = $this->createMock(OpaqueIdProviderInterface::class);
    }

    /**
     * Test that the opaque id is merged with the other client options of the caller (e.g. async bulk).
     *
     * @return void
     */
    public function testBulkKeepsOtherClientOptions(): void
    {
        $this->opaqueIdProvider->method('getOpaqueId')->willReturn(self::OPAQUE_ID);
        $this->esClient->expects($this->once())
            ->method('bulk')
            ->with([
                'body'   => ['foo'],
                'client' => ['future' => 'lazy', 'opaqueId' => self::OPAQUE_ID],
            ])
            ->willReturn([]);

        $this->getClient()->bulk(['body' => ['foo'], 'client' => ['future' => 'lazy']]);
    }

    /**
     * Test that an opaque id set by the caller is not replaced.
     *
     * @return void
     */
    public function testCallerOpaqueIdIsKept(): void
    {
        $this->opaqueIdProvider->expects($this->never())->method('getOpaqueId');
        $params = ['index' => 'idx', 'body' => [], 'client' => ['opaqueId' => 'area=frontend;cnt=foo']];
        $this->esClient->expects($this->once())->method('search')->with($params)->willReturn([]);

        $this->getClient()->search($params);
    }

    /**
     * Test that no client option is added when the feature is disabled.
     *
     * @return void
     */
    public function testDisabled(): void
    {
        $this->opaqueIdProvider->method('getOpaqueId')->willReturn(null);
        $this->esClient->expects($this->once())
            ->method('search')
            ->with(['index' => 'idx', 'body' => []])
            ->willReturn([]);

        $this->getClient()->search(['index' => 'idx', 'body' => []]);
    }

    /**
     * Test that the opaque id is also added to the requests whose params are built by the client itself.
     *
     * @return void
     */
    public function testInternallyBuiltParams(): void
    {
        $this->opaqueIdProvider->method('getOpaqueId')->willReturn(self::OPAQUE_ID);
        $indices = $this->createMock(IndicesNamespace::class);
        $this->esClient->method('indices')->willReturn($indices);
        $indices->expects($this->once())
            ->method('create')
            ->with(['index' => 'idx', 'body' => ['settings' => []], 'client' => ['opaqueId' => self::OPAQUE_ID]]);
        $this->esClient->expects($this->once())
            ->method('ping')
            ->with(['client' => ['opaqueId' => self::OPAQUE_ID]])
            ->willReturn(true);
        $ingest = $this->createMock(IngestNamespace::class);
        $this->esClient->method('ingest')->willReturn($ingest);
        $ingest->expects($this->once())
            ->method('getPipeline')
            ->with(['id' => 'pipeline', 'client' => ['opaqueId' => self::OPAQUE_ID]])
            ->willReturn([]);

        $client = $this->getClient();
        $client->createIndex('idx', ['settings' => []]);
        $client->ping();
        $client->getPipeline('pipeline');
    }

    /**
     * Return the tested client, built on top of the mocked engine client.
     *
     * @return Client
     */
    private function getClient(): Client
    {
        $clientConfiguration = $this->createMock(ClientConfigurationInterface::class);
        $clientConfiguration->method('getOptions')->willReturn([]);
        $clientBuilder = $this->createMock(ClientBuilder::class);
        $clientBuilder->method('build')->willReturn($this->esClient);

        return new Client(
            $clientConfiguration,
            $clientBuilder,
            $this->createMock(LoggerInterface::class),
            $this->opaqueIdProvider
        );
    }
}
