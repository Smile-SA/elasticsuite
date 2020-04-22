<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Plugin\Deprecation\Client;

use Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface;
use Smile\ElasticsuiteCore\Client\ClientBuilder;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;

/**
 * Implements backward compatibility of client settings with 6.x.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ClientPlugin
{
    /**
     * @var string
     */
    private $serverVersion;

    /**
     * @var \Smile\ElasticsuiteCore\Client\ClientBuilder
     */
    private $clientBuilder;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface
     */
    private $clientConfiguration;

    /**
     * Constructor.
     *
     * @param ClusterInfoInterface         $clusterInfo         Cluster information API.
     * @param ClientConfigurationInterface $clientConfiguration Client Configuration
     * @param ClientBuilder                $clientBuilder       Client Builder
     */
    public function __construct(
        ClusterInfoInterface $clusterInfo,
        ClientConfigurationInterface $clientConfiguration,
        ClientBuilder $clientBuilder
    ) {
        $this->serverVersion       = $clusterInfo->getServerVersion();
        $this->clientBuilder       = $clientBuilder;
        $this->clientConfiguration = $clientConfiguration;
    }

    /**
     * @param ClientInterface $client    Client.
     * @param \Closure        $proceed   ClientInterface::putMapping() method
     * @param string          $indexName Index Name
     * @param array           $mapping   Mapping as array
     *
     * @return mixed
     */
    public function aroundPutMapping(
        ClientInterface $client,
        \Closure $proceed,
        $indexName,
        $mapping
    ) {
        if (strcmp($this->serverVersion, "7") >= 0) {
            return $proceed($indexName, $mapping);
        }

        $client   = $this->clientBuilder->build($this->clientConfiguration->getOptions());
        $settings = ['index' => $indexName, 'body' => $mapping];

        if (strcmp($this->serverVersion, "6.7.0") >= 0) {
            // For ES 6.7 and 6.8 we can specify include_type_name=false.
            $settings['include_type_name'] = false;
        } elseif (strcmp($this->serverVersion, "6.7.0") < 0) {
            // For ES < 6.7 we have to go with the default type '_doc'.
            $settings['type'] = '_doc';
        }

        $client->indices()->putMapping($settings);
    }
}
