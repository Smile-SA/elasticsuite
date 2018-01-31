<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Client;

use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterfaceFactory;
use Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface;
use Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;

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
    private $esClient;

    /**
     * Constructor.
     *
     * @param ClientConfigurationInterfaceFactory $clientConfiguration Client configuration factory.
     * @param ClientBuilder                       $clientBuilder       ES client builder.
     * @param LoggerInterface                     $logger              Logger.
     * @param array                               $options             Client options.
     */
    public function __construct(
        ClientConfigurationInterfaceFactory $clientConfigurationFactory,
        ClientBuilder $clientBuilder,
        LoggerInterface $logger,
        $options = []
    ) {
        $clientConfiguration = $clientConfigurationFactory->create(['options' => $options]);
        $this->esClient = $this->createClient($clientConfiguration, $clientBuilder, $logger);
    }

    /**
     * {@inheritDoc}
     */
    public function info()
    {
        return $this->esClient->info();
    }

    /**
     * {@inheritDoc}
     */
    public function ping()
    {
        return $this->esClient->ping();
    }

    /**
     * {@inheritDoc}
     */
    public function createIndex($indexName, $indexSettings)
    {
        $this->esClient->indices()->create(['index' => $indexName, 'body' => $indexSettings]);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteIndex($indexName)
    {
        $this->esClient->indices()->delete(['index' => $indexName]);
    }

    /**
     * {@inheritDoc}
     */
    public function indexExists($indexName)
    {
        return $this->esClient->indices()->exists(['index' => $indexName]);
    }

    /**
     * {@inheritDoc}
     */
    public function putIndexSettings($indexName, $indexSettings)
    {
        $this->esClient->indices()->putSettings(['index' => $indexName, 'body' => $indexSettings]);
    }

    /**
     * {@inheritDoc}
     */
    public function putMapping($indexName, $type, $mapping)
    {
        $this->esClient->indices()->putMapping(['index' => $indexName, 'type'  => $type, 'body'  => [$type => $mapping]]);
    }

    /**
     * {@inheritDoc}
     */
    public function forceMerge($indexName)
    {
        $this->esClient->indices()->forceMerge(['index' => $indexName]);
    }

    /**
     * {@inheritDoc}
     */
    public function refreshIndex($indexName)
    {
        $this->esClient->indices()->refresh(['index' => $indexName]);
    }

    /**
     * {@inheritDoc}
     */
    public function getIndicesNameByAlias($indexAlias)
    {
        $indices = [];
        try {
            $indices = $this->esClient->indices()->getMapping(['index' => $indexAlias]);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            ;
        }

        return array_keys($indices);
    }

    /**
     * {@inheritDoc}
     */
    public function updateAliases($aliasActions)
    {
        $this->esClient->indices()->updateAliases(['body' => ['actions' => $aliasActions]]);
    }

    /**
     * {@inheritDoc}
     */
    public function bulk($bulkParams)
    {
        return $this->esClient->bulk($bulkParams);
    }

    /**
     * {@inheritDoc}
     */
    public function search($params)
    {
        return $this->esClient->search($params);
    }

    /**
     * {@inheritDoc}
     */
    public function analyze($params)
    {
        return $this->esClient->indices()->analyze($params);
    }

    /**
     * {@inheritDoc}
     */
    public function indexStats($indexName)
    {
        return $this->esClient->indices()->stats(['index' => $indexName]);
    }

    /**
     * {@inheritDoc}
     */
    public function termvectors($params)
    {
        return $this->esClient->termvectors($params);
    }

    /**
     * Create an ES Client form configuration.
     *
     * @param ClientConfigurationInterface $clientConfiguration Client configuration.
     * @param ClientBuilder                $clientBuilder       ES client builder.
     * @param LoggerInterface              $logger              Logger
     *
     * @return \Elasticsearch\Client
     */
    private function createClient(
        ClientConfigurationInterface $clientConfiguration,
        ClientBuilder $clientBuilder,
        LoggerInterface $logger
    ) {
        $hosts = $this->getHosts($clientConfiguration);

        if (!empty($hosts)) {
            $clientBuilder->setHosts($hosts);
        }

        if ($clientConfiguration->isDebugModeEnabled()) {
            $clientBuilder->setLogger($logger);
        }

        return $clientBuilder->build();
    }

    /**
     * Return hosts config used to connect to the cluster.
     *
     * @param ClientConfigurationInterface $clientConfiguration Client configuration.
     *
     * @return array
     */
    private function getHosts(ClientConfigurationInterface $clientConfiguration)
    {
        $hosts = [];

        foreach ($clientConfiguration->getServerList() as $host) {
            if (!empty($host)) {
                list($hostname, $port) = array_pad(explode(':', $host, 2), 2, 9200);
                $currentHostConfig = [
                    'host'   => $hostname,
                    'port'   => $port,
                    'scheme' => $clientConfiguration->getScheme(),
                ];

                if ($clientConfiguration->isHttpAuthEnabled()) {
                    $currentHostConfig['user'] = $clientConfiguration->getHttpAuthUser();
                    $currentHostConfig['pass'] = $clientConfiguration->getHttpAuthPassword();
                }

                $hosts[] = $currentHostConfig;
            }
        }

        return $hosts;
    }
}
