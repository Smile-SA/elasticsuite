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

use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use OpenSearch\ConnectionPool\Selectors\StickyRoundRobinSelector;

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
     * @var \OpenSearch\ClientBuilder
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
        'enable_aws_sig4'       => false,
        'aws_region'            => 'eu-central-1',
        'aws_service'           => 'es',
    ];

    /**
     * @var string
     */
    private $selector;

    /**
     * @var array
     */
    private $namespaceBuilders = [];

    /**
     * @var callable|null
     */
    private $sig4CredentialsProvider = null;

    /**
     * Constructor.
     *
     * @param \OpenSearch\ClientBuilder $clientBuilder     Client builder.
     * @param \Psr\Log\LoggerInterface  $logger            Logger.
     * @param string                    $selector          Node Selector.
     * @param array                     $namespaceBuilders NamespaceBuilders to extend the client if needed.
     */
    public function __construct(
        \OpenSearch\ClientBuilder $clientBuilder,
        \Psr\Log\LoggerInterface $logger,
        string $selector = StickyRoundRobinSelector::class,
        array $namespaceBuilders = []
    ) {
        $this->clientBuilder     = $clientBuilder;
        $this->logger            = $logger;
        $this->selector          = $selector;
        $this->namespaceBuilders = $namespaceBuilders;
    }

    /**
     * Build an ES client from options.
     *
     * We decided to use the OpenSearch client because he is versatile and can work with :
     *
     * - Elasticsearch 7.x
     * - Elasticsearch 8.x
     * - Opensearch 1.x
     * - Opensearch 2.x
     *
     * At least, for now...
     *
     * The Elasticsearch client had a change in FQCN between v7 and v8 that would require a huge rework.
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param array $options Client options. See self::defaultOptions for available options.
     *
     * @return \OpenSearch\Client
     */
    public function build(array $options = []): \OpenSearch\Client
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

        $handlerParams = [];
        if ($options['max_parallel_handles']) {
            $handlerParams = ['max_handles' => (int) $options['max_parallel_handles']];
        }
        $handler = \OpenSearch\ClientBuilder::defaultHandler($handlerParams);
        $clientBuilder->setHandler($handler);

        if ($this->isAwsSig4Enabled($options)) {
            // The ->setSigV4* methods are only available starting from opensearch/opensearch-php 2.0.1.
            try {
                $clientBuilder->setSigV4Region($options['aws_region'])
                              ->setSigV4Service($options['aws_service'])
                              ->setSigV4CredentialProvider($this->getSig4CredentialsProvider($options));
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
                $message = "Cannot configure SigV4 on OpenSearch client. " .
                    "This can be due to an outdated (<2.0.1) version of the opensearch-project/opensearch-php package.";

                throw new \Exception($message . " Error was : " . $exception->getMessage());
            }
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

        foreach ($this->namespaceBuilders as $namespaceBuilder) {
            $clientBuilder->registerNamespace($namespaceBuilder);
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
    private function getHosts(array $options): array
    {
        $hosts = [];

        if (is_string($options['servers'])) {
            $options['servers'] = explode(',', $options['servers']);
        }

        foreach ($options['servers'] as $host) {
            if (!empty($host)) {
                [$hostname, $port] = array_pad(explode(':', trim(preg_replace('/^https?:\/\//', '', $host)), 2), 2, 9200);
                preg_match('/^(https?):\/\//', $host, $matches);

                $currentHostConfig = [
                    'host'   => $hostname,
                    'port'   => $port,
                    'scheme' => isset($options['enable_https_mode']) ? 'https' : $matches[1] ?? 'http',
                ];

                if ($options['enable_http_auth']) {
                    $currentHostConfig['user'] = $options['http_auth_user'];
                    $currentHostConfig['pass'] = $options['http_auth_pwd'];
                }

                if ($this->isAwsSig4Enabled($options) === true) {
                    $currentHostConfig['scheme'] = 'https'; // AOSS is alway behind HTTPS url.
                    $currentHostConfig['host']   = $host;   // Use raw host string for AOSS.
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
     *
     * @return array
     */
    private function getConnectionParams(array $options): array
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
     * Check if AWS Signature v4 should be used for signing the requests
     *
     * @param array $options The Options
     *
     * @return bool
     */
    private function isAwsSig4Enabled($options)
    {
        return (((bool) $options['enable_aws_sig4']) === true);
    }

    /**
     * Get CredentialProvider Instance to be used with Sig4 authentication.
     *
     * @param array $options The client options
     *
     * @return callable
     */
    private function getSig4CredentialsProvider($options)
    {
        if (null === $this->sig4CredentialsProvider) {
            $this->sig4CredentialsProvider = CredentialProvider::fromCredentials(
                new Credentials(
                    $options['aws_key'],
                    $options['aws_secret'],
                    isset($options['aws_token']) ? $options['aws_token'] : null,
                    isset($options['aws_expires']) ? $options['aws_expires'] : null
                )
            );
        }

        return $this->sig4CredentialsProvider;
    }
}
