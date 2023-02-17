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

namespace Smile\ElasticsuiteCore\Plugin\Deprecation\Search\Request;

use Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface;

/**
 * Plugin to remove cutoff_frequency parameter on Query types that used it.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class QueryInterfacePlugin
{
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
     * @param \Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface $clusterInfo Cluster information API.
     */
    public function __construct(\Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface $clusterInfo)
    {
        $this->serverVersion      = $clusterInfo->getServerVersion();
        $this->serverDistribution = $clusterInfo->getServerDistribution();
    }

    /**
     * Discard cutoff_frequency value to prevent the query builder to inject it into the query sent to ES.
     * cutoff_frequency is deprecated since Elasticsearch 7.3 and has been removed in ES 8.
     * @see https://github.com/elastic/elasticsearch/issues/37096
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html#query-dsl-match-query-cutoff
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\QueryInterface $subject The Query
     * @param float                                                 $result  Precedent Result
     *
     * @return float|false
     */
    public function afterGetCutoffFrequency(\Smile\ElasticsuiteCore\Search\Request\QueryInterface $subject, $result)
    {
        if ($this->serverDistribution === ClusterInfoInterface::DISTRO_ES) {
            if (version_compare($this->clusterInfo->getServerVersion(), "8.0.0") >= 0) {
                $result = 0; // Will be evaluated as false and discarded by the Query Builder.
            }
        } elseif ($this->serverDistribution === ClusterInfoInterface::DISTRO_OS) {
            if (version_compare($this->clusterInfo->getServerVersion(), "2.0.0") >= 0) {
                $result = 0; // Will be evaluated as false and discarded by the Query Builder.
            }
        }

        return $result;
    }
}
