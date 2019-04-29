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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Client;

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
        'is_debug_mode_enabled' => false,
    ];

    /**
     * Constructor.
     *
     * @param \Elasticsearch\ClientBuilder $clientBuilder Client builder.
     * @param \Psr\Log\LoggerInterface     $logger        Logger.
     */
    public function __construct(\Elasticsearch\ClientBuilder $clientBuilder, \Psr\Log\LoggerInterface $logger)
    {
        $this->clientBuilder = $clientBuilder;
        $this->logger        = $logger;
    }

    /**
     * Build an ES client from options.
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
                list($hostname, $port) = array_pad(explode(':', trim($host), 2), 2, 9200);
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
}
