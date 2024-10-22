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

namespace Smile\ElasticsuiteCore\Client;

use OpenSearch\Common\Exceptions\Missing404Exception;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;

/**
 * ElasticSearch client implementation.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 *
 * @SuppressWarnings(TooManyPublicMethods)
 */
class Client implements ClientInterface
{
    /**
     * @var \OpenSearch\Client
     */
    private $esClient = null;

    /**
     * @var ClientConfigurationInterface
     */
    private $clientConfiguration;

    /**
     * @var ClientBuilder
     */
    private $clientBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param ClientConfigurationInterface $clientConfiguration Client configuration factory.
     * @param ClientBuilder                $clientBuilder       ES client builder.
     * @param LoggerInterface              $logger              Logger.
     */
    public function __construct(
        ClientConfigurationInterface $clientConfiguration,
        ClientBuilder $clientBuilder,
        LoggerInterface $logger
    ) {
        $this->clientConfiguration = $clientConfiguration;
        $this->clientBuilder = $clientBuilder;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function info()
    {
        return $this->getEsClient()->info();
    }

    /**
     * {@inheritDoc}
     */
    public function nodes()
    {
        return $this->getEsClient()->nodes();
    }

    /**
     * {@inheritDoc}
     */
    public function cluster()
    {
        return $this->getEsClient()->cluster();
    }

    /**
     * {@inheritDoc}
     */
    public function ping()
    {
        return $this->getEsClient()->ping();
    }

    /**
     * {@inheritDoc}
     */
    public function createIndex($indexName, $indexSettings)
    {
        $this->getEsClient()->indices()->create(['index' => $indexName, 'body' => $indexSettings]);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteIndex($indexName)
    {
        $this->getEsClient()->indices()->delete(['index' => $indexName]);
    }

    /**
     * {@inheritDoc}
     */
    public function indexExists($indexName)
    {
        return $this->getEsClient()->indices()->exists(['index' => $indexName]);
    }

    /**
     * {@inheritDoc}
     */
    public function putIndexSettings($indexName, $indexSettings)
    {
        $this->getEsClient()->indices()->putSettings(['index' => $indexName, 'body' => $indexSettings]);
    }

    /**
     * {@inheritDoc}
     */
    public function putMapping($indexName, $mapping)
    {
        $this->getEsClient()->indices()->putMapping(['index' => $indexName, 'body'  => $mapping]);
    }

    /**
     * {@inheritDoc}
     */
    public function getMapping($indexName)
    {
        return $this->getEsClient()->indices()->getMapping(['index' => $indexName]);
    }

    /**
     * {@inheritDoc}
     */
    public function getSettings($indexName)
    {
        return $this->getEsClient()->indices()->getSettings(['index' => $indexName]);
    }

    /**
     * {@inheritDoc}
     */
    public function forceMerge($indexName)
    {
        $this->getEsClient()->indices()->forceMerge(['index' => $indexName]);
    }

    /**
     * {@inheritDoc}
     */
    public function refreshIndex($indexName)
    {
        $this->getEsClient()->indices()->refresh(['index' => $indexName]);
    }

    /**
     * {@inheritDoc}
     */
    public function getIndicesNameByAlias($indexAlias)
    {
        $indices = [];
        try {
            $indices = $this->getEsClient()->indices()->getMapping(['index' => $indexAlias]);
        } catch (\OpenSearch\Common\Exceptions\Missing404Exception $e) {
            ;
        }

        return array_keys($indices);
    }

    /**
     * {@inheritDoc}
     */
    public function getIndexAliases($params = []): array
    {
        return $this->getEsClient()->indices()->getAliases($params);
    }

    /**
     * {@inheritDoc}
     */
    public function updateAliases($aliasActions)
    {
        $this->getEsClient()->indices()->updateAliases(['body' => ['actions' => $aliasActions]]);
    }

    /**
     * {@inheritDoc}
     */
    public function bulk($bulkParams)
    {
        return $this->getEsClient()->bulk($bulkParams);
    }

    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    public function search($params)
    {
        try {
            $response = $this->getEsClient()->search($params);
        } catch (\Exception $e) {
            // If debug is enabled, no need to log, the ES client would already have done it.
            if (false === $this->clientConfiguration->isDebugModeEnabled()) {
                $requestInfo = json_encode($params, JSON_PRESERVE_ZERO_FRACTION + JSON_INVALID_UTF8_SUBSTITUTE);
                $this->logger->error(sprintf("Search Request Failure [error] : %s", $e->getMessage()));
                $this->logger->error(sprintf("Search Request Failure [request] : %s", $requestInfo));
            }
            throw $e;
        }

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function analyze($params)
    {
        return $this->getEsClient()->indices()->analyze($params);
    }

    /**
     * {@inheritDoc}
     */
    public function indexStats($indexName): array
    {
        try {
            $stats = $this->getEsClient()->indices()->stats(['index' => $indexName]);
        } catch (\Exception $e) {
            throw new Missing404Exception($e->getMessage());
        }

        return $stats;
    }

    /**
     * {@inheritDoc}
     */
    public function termvectors($params)
    {
        return $this->getEsClient()->termvectors($params);
    }

    /**
     * {@inheritDoc}
     */
    public function mtermvectors($params)
    {
        return $this->getEsClient()->mtermvectors($params);
    }

    /**
     * {@inheritDoc}
     */
    public function reindex(array $params): array
    {
        return $this->getEsClient()->reindex($params);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteByQuery(array $params): array
    {
        return $this->getEsClient()->deleteByQuery($params);
    }

    /**
     * {@inheritDoc}
     */
    public function updateByQuery(array $params): array
    {
        return $this->getEsClient()->updateByQuery($params);
    }

    /**
     * {@inheritDoc}
     */
    public function putPipeline(array $params): array
    {
        return $this->getEsClient()->ingest()->putPipeline($params);
    }

    /**
     * {@inheritDoc}
     */
    public function getPipeline(string $name): array
    {
        return $this->getEsClient()->ingest()->getPipeline(['id' => $name]);
    }

    /**
     * @return \OpenSearch\Client
     */
    private function getEsClient(): \OpenSearch\Client
    {
        if ($this->esClient === null) {
            $this->esClient = $this->clientBuilder->build($this->clientConfiguration->getOptions());
        }

        return $this->esClient;
    }
}
