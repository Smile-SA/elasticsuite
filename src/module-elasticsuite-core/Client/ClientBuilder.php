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

use Opensearch\ConnectionPool\Selectors\StickyRoundRobinSelector;
use Http\Adapter\Guzzle7\Client;

/**
 * ElasticSearch client builder.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ClientBuilder
{
    /**
     * @var \OpenSearch\ClientBuilder|\Elastic\Elasticsearch\ClientBuilder
     */
    private $clientBuilder;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $defaultOptions = [
        'servers'               => 'localhost:9200',
        'enable_http_auth'      => false,
        'http_auth_user'        => null,
        'http_auth_pwd'         => null,
        'http_auth_encoded'     => false,
        'is_debug_mode_enabled' => false,
        'max_parallel_handles'  => 100, // As per default Elasticsearch Handler configuration.
        'max_retries'           => 2,
        'verify'                => true,
    ];

    /**
     * @var string
     */
    private $selector;

    /**
     * @var integer
     */
    private $clientVersion = 7;

    /**
     * Constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger   Logger.
     * @param string                   $selector Node Selector.
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        $selector = StickyRoundRobinSelector::class
    ) {
        $this->clientVersion = $this->getClientVersion();
        $this->logger        = $logger;
        $this->selector      = $selector;
    }

    /**
     * Build an ES client from options.
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @param array $options Client options. See self::defaultOptions for available options.
     *
     * @return \OpenSearch\Client|\Elasticsearch\Client|\Elastic\Elasticsearch\Client
     */
    public function build($options = [])
    {
        $options       = array_merge($this->defaultOptions, $options);

        $clientBuilder = $this->getClientBuilder()->create();

        $hosts = $this->getHosts($options);

        if (!empty($hosts)) {
            if ($this->clientVersion === 7) {
                $clientBuilder->setHosts($hosts);
            } elseif ($this->clientVersion === 8) {
                $preparedHosts = [];
                foreach ($hosts as $host) {
                    if ($options['enable_http_auth'] && !empty($host['user']) && (!empty($host['pass']))) {
                        $clientBuilder->setBasicAuthentication($host['user'], $host['pass']);
                    }
                    $preparedHosts[] = $host['scheme'] . '://' . $host['host'] . ":" . $host['port'];
                }
                $clientBuilder->setHosts($preparedHosts);
            }
        }

        if ($options['is_debug_mode_enabled']) {
            $clientBuilder->setLogger($this->logger);
            // The method setTracer() was removed in v8.
            if (($this->clientVersion === 7) && method_exists($clientBuilder, 'setTracer')) {
                $clientBuilder->setTracer($this->logger);
            }
        }

        if ($options['max_parallel_handles']) {
            // The method defaultHandler() was removed in v8.
            if (($this->clientVersion === 7) && method_exists($clientBuilder, "defaultHandler")) {
                $handlerParams = ['max_handles' => (int) $options['max_parallel_handles']];
                $handler       = $this->clientBuilder::defaultHandler($handlerParams);
                $clientBuilder->setHandler($handler);
            } elseif (method_exists($clientBuilder, 'setAsyncHttpClient')) {
                $asyncClient = \Http\Adapter\Guzzle7\Client::createWithConfig([]);
                $clientBuilder->setAsyncHttpClient($asyncClient);
            }
        }

        $connectionParams = $this->getEncodedConnectionParams($options);

        if (!empty($connectionParams)) {
            // The setConnectionParams() was removed in v8.
            if (($this->clientVersion === 7) && method_exists($clientBuilder, 'setConnectionParams')) {
                $this->clientBuilder->setConnectionParams($connectionParams);
            }
        }

        if ($options['max_retries'] > 0) {
            $clientBuilder->setRetries((int) $options['max_retries']);
        }

        if (array_key_exists('verify', $options)) {
            $clientBuilder->setSSLVerification($options['verify']);
        }

        if (null !== $this->selector
            && ($this->clientVersion === 7)
            && class_exists($this->selector)
            && class_exists(StickyRoundRobinSelector::class)
        ) {
            $selector = (count($hosts) > 1) ? $this->selector : StickyRoundRobinSelector::class;
            $clientBuilder->setSelector($selector);
        }

        return $clientBuilder->build();
    }

    /**
     * Return hosts config used to connect to the cluster.
     *
     * @param array $options Client options. See self::defaultOptions for available options.
     *
     * @return array
     */
    private function getHosts($options)
    {
        $hosts = [];

        if (is_string($options['servers'])) {
            $options['servers'] = explode(',', $options['servers']);
        }

        foreach ($options['servers'] as $host) {
            if (!empty($host)) {
                [$hostname, $port] = array_pad(explode(':', trim($host), 2), 2, 9200);
                $currentHostConfig = [
                    'host'   => $hostname,
                    'port'   => $port,
                    'scheme' => isset($options['enable_https_mode']) ? 'https' : $options['scheme'] ?? 'http',
                ];

                if ($options['enable_http_auth']) {
                    $currentHostConfig['user'] = $options['http_auth_user'];
                    $currentHostConfig['pass'] = $options['http_auth_pwd'];
                }

                $hosts[] = $currentHostConfig;
            }
        }

        return $hosts;
    }

    /**
     * Return HTTP Authentication parameters used to connect to the cluster if any
     *
     * @param array $options Client options. See self::defaultOptions for available options.
     * @return array
     */
    private function getEncodedConnectionParams($options)
    {
        return (
            !empty($options['http_auth_user']) &&
            !empty($options['http_auth_pwd']) &&
            $options['http_auth_encoded']
        ) ? [
            'client' => [
                'headers' => [
                    'Authorization' => [
                        'Basic ' . base64_encode($options['http_auth_user'] . ':' . $options['http_auth_pwd']),
                    ],
                ],
            ],
        ] : [];
    }

    /**
     * Fetch the Elasticsearch Client builder.
     *
     * FQCN changed between version 7 and 8 of the client.
     *
     * Magento also requires the OpenSearch client library since 2.4.6. So it should always be here.
     *
     * OS PHP client v1/v2 : \OpenSearch\ClientBuilder
     * ES PHP client v7 : \Elasticsearch\ClientBuilder
     * ES PHP client v8 : \Elastic\Elasticsearch\ClientBuilder
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @return \OpenSearch\ClientBuilder|\Elasticsearch\ClientBuilder|\Elastic\Elasticsearch\ClientBuilder
     */
    private function getClientBuilder()
    {
        if (null === $this->clientBuilder) {
            // If the Elasticsearch v8 client is there, it's probably been installed on purpose.
            // It's not required by Magento. So let's use it if people added it.
            if (class_exists("\Elastic\Elasticsearch\ClientBuilder")) {
                $this->clientBuilder = new \Elastic\Elasticsearch\ClientBuilder();
            } elseif (class_exists("\Elasticsearch\ClientBuilder")) {
                $this->clientBuilder = new \Elasticsearch\ClientBuilder();
            } elseif (class_exists("\OpenSearch\ClientBuilder")) {
                $this->clientBuilder = new \OpenSearch\ClientBuilder();
            } else {
                $message = "Impossible to create Elasticsearch client builder. "
                    . "Neither \Elasticsearch\ClientBuilder nor \Elastic\Elasticsearch\ClientBuilder "
                    . "nor \OpenSearch\ClientBuilder classes exists.";

                throw new \LogicException($message);
            }
        }

        return $this->clientBuilder;
    }

    /**
     * Fetch the Elasticsearch Client builder version.
     *
     * FQCN changed between version 7 and 8 of the client.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @return integer
     */
    private function getClientVersion()
    {
        // If the Elasticsearch v8 client is there, it's probably been installed on purpose.
        // It's not required by Magento. So let's use it if people added it.
        if (class_exists("\Elastic\Elasticsearch\ClientBuilder")) {
            return 8;
        } elseif (class_exists("\Elasticsearch\ClientBuilder") || class_exists("\OpenSearch\ClientBuilder")) {
            // ES PHP client v7 and OS PHP client v1/v2 are the same... for now...
            // Let's assume they are identical and consider they can be used the same way.
            return 7;
        } else {
            $message = "Impossible to fetch Elasticsearch client version. "
                . "Neither \Elasticsearch\ClientBuilder nor \Elastic\Elasticsearch\ClientBuilder "
                . "nor \OpenSearch\ClientBuilder classes exists.";

            throw new \LogicException($message);
        }
    }
}
