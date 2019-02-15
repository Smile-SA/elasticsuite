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

/**
 * Implements backward compatibility of client settings with ES 5.x / 6.x.
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
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface $clusterInfo Cluster information API.
     */
    public function __construct(\Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface $clusterInfo)
    {
        $this->serverVersion = $clusterInfo->getServerVersion();
    }

    /**
     * Remove index.max_shingle_diff and index.max_ngram_diff for indices < ES7.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Smile\ElasticsuiteCore\Api\Client\ClientInterface $client Client.
     *
     * @return array
     */
    public function beforeCreateIndex(\Smile\ElasticsuiteCore\Api\Client\ClientInterface $client, $indexName, $indexSettings)
    {
        if (strcmp($this->serverVersion, "6") < 0) {
            if (isset($indexSettings['settings'])) {
                unset($indexSettings['settings']['max_shingle_diff']);
                unset($indexSettings['settings']['max_ngram_diff']);
            }
        }

        return [$indexName, $indexSettings];
    }
}
