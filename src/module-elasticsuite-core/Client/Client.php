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

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Client\OpaqueIdProviderInterface;

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
     * @var \Elasticsearch\Client
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
     * @var OpaqueIdProviderInterface
     */
    private $opaqueIdProvider;

    /**
     * Constructor.
     *
     * @param ClientConfigurationInterface   $clientConfiguration Client configuration factory.
     * @param ClientBuilder                  $clientBuilder       ES client builder.
     * @param LoggerInterface                $logger              Logger.
     * @param OpaqueIdProviderInterface|null $opaqueIdProvider    X-Opaque-Id provider.
     */
    public function __construct(
        ClientConfigurationInterface $clientConfiguration,
        ClientBuilder $clientBuilder,
        LoggerInterface $logger,
        ?OpaqueIdProviderInterface $opaqueIdProvider = null
    ) {
        $this->clientConfiguration = $clientConfiguration;
        $this->clientBuilder = $clientBuilder;
        $this->logger = $logger;
        $this->opaqueIdProvider = $opaqueIdProvider ?? ObjectManager::getInstance()->get(OpaqueIdProviderInterface::class);
    }

    /**
     * {@inheritDoc}
     */
    public function info()
    {
        return $this->getEsClient()->info($this->addOpaqueId([]));
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
        return $this->getEsClient()->ping($this->addOpaqueId([]));
    }

    /**
     * {@inheritDoc}
     */
    public function createIndex($indexName, $indexSettings)
    {
        $this->getEsClient()->indices()->create($this->addOpaqueId(['index' => $indexName, 'body' => $indexSettings]));
    }

    /**
     * {@inheritDoc}
     */
    public function deleteIndex($indexName)
    {
        $this->getEsClient()->indices()->delete($this->addOpaqueId(['index' => $indexName]));
    }

    /**
     * {@inheritDoc}
     */
    public function indexExists($indexName)
    {
        return $this->getEsClient()->indices()->exists($this->addOpaqueId(['index' => $indexName]));
    }

    /**
     * {@inheritDoc}
     */
    public function putIndexSettings($indexName, $indexSettings)
    {
        $this->getEsClient()->indices()->putSettings($this->addOpaqueId(['index' => $indexName, 'body' => $indexSettings]));
    }

    /**
     * {@inheritDoc}
     */
    public function putMapping($indexName, $mapping)
    {
        $this->getEsClient()->indices()->putMapping($this->addOpaqueId(['index' => $indexName, 'body'  => $mapping]));
    }

    /**
     * {@inheritDoc}
     */
    public function getMapping($indexName)
    {
        return $this->getEsClient()->indices()->getMapping($this->addOpaqueId(['index' => $indexName]));
    }

    /**
     * {@inheritDoc}
     */
    public function getSettings($indexName)
    {
        return $this->getEsClient()->indices()->getSettings($this->addOpaqueId(['index' => $indexName]));
    }

    /**
     * {@inheritDoc}
     */
    public function getSpecificSettings($indexName, $settings)
    {
        return $this->getEsClient()->indices()->getSettings($this->addOpaqueId(['index' => $indexName, 'name' => $settings]));
    }

    /**
     * {@inheritDoc}
     */
    public function forceMerge($indexName)
    {
        $this->getEsClient()->indices()->forceMerge($this->addOpaqueId(['index' => $indexName]));
    }

    /**
     * {@inheritDoc}
     */
    public function refreshIndex($indexName)
    {
        $this->getEsClient()->indices()->refresh($this->addOpaqueId(['index' => $indexName]));
    }

    /**
     * {@inheritDoc}
     */
    public function getIndicesNameByAlias($indexAlias)
    {
        $indices = [];
        try {
            $indices = $this->getEsClient()->indices()->getMapping($this->addOpaqueId(['index' => $indexAlias]));
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            ;
        }

        return array_keys($indices);
    }

    /**
     * {@inheritDoc}
     */
    public function getIndexAliases($params = []): array
    {
        return $this->getEsClient()->indices()->getAliases($this->addOpaqueId($params));
    }

    /**
     * {@inheritDoc}
     */
    public function updateAliases($aliasActions)
    {
        $this->getEsClient()->indices()->updateAliases($this->addOpaqueId(['body' => ['actions' => $aliasActions]]));
    }

    /**
     * {@inheritDoc}
     */
    public function bulk($bulkParams)
    {
        return $this->getEsClient()->bulk($this->addOpaqueId($bulkParams));
    }

    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    public function search($params)
    {
        $params = $this->addOpaqueId($params);

        try {
            $response = $this->getEsClient()->search($params);
        } catch (\Exception $e) {
            if ($this->clientConfiguration->isLoggingErrorRequest()) {
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
        return $this->getEsClient()->indices()->analyze($this->addOpaqueId($params));
    }

    /**
     * {@inheritDoc}
     */
    public function indexStats($indexName): array
    {
        try {
            $stats = $this->getEsClient()->indices()->stats($this->addOpaqueId(['index' => $indexName]));
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
        return $this->getEsClient()->termvectors($this->addOpaqueId($params));
    }

    /**
     * {@inheritDoc}
     */
    public function mtermvectors($params)
    {
        return $this->getEsClient()->mtermvectors($this->addOpaqueId($params));
    }

    /**
     * {@inheritDoc}
     */
    public function reindex(array $params): array
    {
        return $this->getEsClient()->reindex($this->addOpaqueId($params));
    }

    /**
     * {@inheritDoc}
     */
    public function deleteByQuery(array $params): array
    {
        return $this->getEsClient()->deleteByQuery($this->addOpaqueId($params));
    }

    /**
     * {@inheritDoc}
     */
    public function updateByQuery(array $params): array
    {
        return $this->getEsClient()->updateByQuery($this->addOpaqueId($params));
    }

    /**
     * Add the X-Opaque-Id identifying the origin of the request to the request params,
     * unless it has already been set by the caller or the feature is disabled.
     *
     * @param array $params Request params.
     *
     * @return array
     */
    private function addOpaqueId(array $params): array
    {
        if (!isset($params['client']['opaqueId'])) {
            $opaqueId = $this->opaqueIdProvider->getOpaqueId();
            if ($opaqueId !== null) {
                $params['client']['opaqueId'] = $opaqueId;
            }
        }

        return $params;
    }

    /**
     * @return \Elasticsearch\Client
     */
    private function getEsClient(): \Elasticsearch\Client
    {
        if ($this->esClient === null) {
            $this->esClient = $this->clientBuilder->build($this->clientConfiguration->getOptions());
        }

        return $this->esClient;
    }
}
