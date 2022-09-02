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
     * Constructor.
     *
     * @param ClientConfigurationInterface $clientConfiguration Client configuration factory.
     * @param ClientBuilder                $clientBuilder       ES client builder.
     */
    public function __construct(ClientConfigurationInterface $clientConfiguration, ClientBuilder $clientBuilder)
    {
        $this->clientConfiguration = $clientConfiguration;
        $this->clientBuilder = $clientBuilder;
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
     */
    public function search($params)
    {
        return $this->getEsClient()->search($params);
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
