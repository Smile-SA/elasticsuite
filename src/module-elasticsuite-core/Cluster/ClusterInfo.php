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

namespace Smile\ElasticsuiteCore\Cluster;

use Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface;

/**
 * Default implementation of cluster info API
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ClusterInfo implements ClusterInfoInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Api\Client\ClientInterface
     */
    private $client;

    /**
     * @var string
     */
    private $serverVersion;

    /**
     * @var string
     */
    private $serverDistribution;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Api\Client\ClientInterface $client ElasticSearch client.
     */
    public function __construct(\Smile\ElasticsuiteCore\Api\Client\ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritDoc}
     */
    public function getServerVersion()
    {
        if ($this->serverVersion === null) {
            $this->getInfo();
        }

        return $this->serverVersion;
    }

    /**
     * {@inheritDoc}
     */
    public function getServerDistribution()
    {
        if ($this->serverDistribution === null) {
            $this->getInfo();
        }

        return $this->serverDistribution;
    }

    /**
     * Get server info.
     *
     * @return array
     */
    private function getInfo()
    {
        $info                     = $this->client->info();
        $this->serverVersion      = $info['version']['number'];
        $this->serverDistribution = $info['version']['distribution'] ?? self::DISTRO_ES;

        return $info;
    }
}
