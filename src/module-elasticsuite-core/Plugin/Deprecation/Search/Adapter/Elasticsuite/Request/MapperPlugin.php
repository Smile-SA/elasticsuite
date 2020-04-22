<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Plugin\Deprecation\Search\Adapter\Elasticsuite\Request;

use Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Mapper;
use Smile\ElasticsuiteCore\Search\RequestInterface;

/**
 * Plugin to remove track_total_hits on ES version < 7
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class MapperPlugin
{
    /**
     * @var ClusterInfoInterface
     */
    private $clusterInfo;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface $clusterInfo Cluster information API.
     */
    public function __construct(\Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface $clusterInfo)
    {
        $this->clusterInfo = $clusterInfo;
    }

    /**
     * Remove track_total_hits for ES<7
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Mapper $subject Request Mapper
     * @param array                                                              $result  Request result as array
     * @param \Smile\ElasticsuiteCore\Search\RequestInterface                    $request Request object
     *
     * @return array
     */
    public function afterBuildSearchRequest(Mapper $subject, array $result, RequestInterface $request)
    {
        if (strcmp($this->clusterInfo->getServerVersion(), "7") < 0) {
            unset($result['track_total_hits']);
        }

        return $result;
    }
}
