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

use Smile\ElasticsuiteCore\Api\Client\ClientFactoryInterface;

/**
 * Provides a simple way to retrieve an Elasticsearch client.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 *
 * @deprecated Use Smile\ElasticsuiteCore\Api\Client\ClientInterface instead.
 */
class ClientFactory implements ClientFactoryInterface
{
    /**
     * @var \Elasticsearch\ClientBuilder
     */
    private $clientBuilder;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface
     */
    private $clientConfiguration;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Elasticsearch\Client
     */
    private $client;

    /**
     * Factory constructor.
     *
     * @param \Elasticsearch\ClientBuilder                                    $clientBuilder       Elasticsearch client builder.
     * @param \Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface $clientConfiguration Elasticsearch configuration helper.
     * @param \Psr\Log\LoggerInterface                                        $logger              Elasticsearch logger.
     */
    public function __construct(
        \Elasticsearch\ClientBuilder $clientBuilder,
        \Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface $clientConfiguration,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->clientBuilder        = $clientBuilder;
        $this->clientConfiguration  = $clientConfiguration;
        $this->logger               = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function createClient()
    {
        if ($this->client === null) {
            $hosts         = $this->getHosts();
            $this->clientBuilder->setHosts($hosts);

            if ($this->clientConfiguration->isDebugModeEnabled()) {
                $this->clientBuilder->setLogger($this->logger);
            }

            $this->client = $this->clientBuilder->build();
        }

        return $this->client;
    }

    /**
     * Return hosts config used to connect to the cluster.
     *
     * @return array
     */
    private function getHosts()
    {
        $hosts               = [];
        $clientConfiguration = $this->clientConfiguration;

        foreach ($clientConfiguration->getServerList() as $host) {
            if (!empty($host)) {
                list($hostname, $port) = array_pad(explode(':', $host, 2), 2, "9200");
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
