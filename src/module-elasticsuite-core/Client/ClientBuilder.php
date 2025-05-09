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

use Elasticsearch\ConnectionPool\Selectors\StickyRoundRobinSelector;

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
     * @var \Elasticsearch\ClientBuilder
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
     * Constructor.
     *
     * @param \Elasticsearch\ClientBuilder $clientBuilder Client builder.
     * @param \Psr\Log\LoggerInterface     $logger        Logger.
     * @param string                       $selector      Node Selector.
     */
    public function __construct(
        \Elasticsearch\ClientBuilder $clientBuilder,
        \Psr\Log\LoggerInterface $logger,
        $selector = StickyRoundRobinSelector::class
    ) {
        $this->clientBuilder = $clientBuilder;
        $this->logger        = $logger;
        $this->selector      = $selector;
    }

    /**
     * Build an ES client from options.
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param array $options Client options. See self::defaultOptions for available options.
     *
     * @return \Elasticsearch\Client
     */
    public function build($options = [])
    {
        $options       = array_merge($this->defaultOptions, $options);

        $clientBuilder = $this->clientBuilder->create();

        $hosts = $this->getHosts($options);

        if (!empty($hosts)) {
            $clientBuilder->setHosts($hosts);
        }

        if ($options['is_debug_mode_enabled']) {
            $clientBuilder->setLogger($this->logger);
            $clientBuilder->setTracer($this->logger);
        }

        if ($options['max_parallel_handles']) {
            $handlerParams = ['max_handles' => (int) $options['max_parallel_handles']];
            $handler = \Elasticsearch\ClientBuilder::defaultHandler($handlerParams);
            $clientBuilder->setHandler($handler);
        }

        $connectionParams = $this->getConnectionParams($options);

        if (!empty($connectionParams)) {
            $this->clientBuilder->setConnectionParams($connectionParams);
        }

        if ($options['max_retries'] > 0) {
            $clientBuilder->setRetries((int) $options['max_retries']);
        }

        if (array_key_exists('verify', $options)) {
            $clientBuilder->setSSLVerification($options['verify']);
        }

        if (null !== $this->selector) {
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
    private function getConnectionParams($options)
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
}
