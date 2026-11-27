<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Plugin\Deprecation\Client;

use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface;

/**
 * Adds back the document type to the multi term vectors docs for Elasticsearch < 7.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class TermVectorsPlugin
{
    /**
     * @var ClusterInfoInterface
     */
    private $clusterInfo;

    /**
     * @var boolean|null
     */
    private $useDocumentType;

    /**
     * Constructor.
     *
     * @param ClusterInfoInterface $clusterInfo Cluster information API.
     */
    public function __construct(ClusterInfoInterface $clusterInfo)
    {
        $this->clusterInfo = $clusterInfo;
    }

    /**
     * Add the '_doc' document type to the multi term vectors docs for Elasticsearch < 7.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param ClientInterface $subject Client.
     * @param array           $params  Multi term vectors request params.
     *
     * @return array
     */
    public function beforeMtermvectors(ClientInterface $subject, $params)
    {
        if ($this->useDocumentType() && is_array($params['body']['docs'] ?? null)) {
            foreach ($params['body']['docs'] as &$doc) {
                if (!isset($doc['_type'])) {
                    $doc['_type'] = '_doc';
                }
            }
            unset($doc);
        }

        return [$params];
    }

    /**
     * Returns true if the server needs a document type (Elasticsearch < 7).
     *
     * @return boolean
     */
    private function useDocumentType()
    {
        if ($this->useDocumentType === null) {
            $this->useDocumentType = false;
            if ($this->clusterInfo->getServerDistribution() === ClusterInfoInterface::DISTRO_ES) {
                if (version_compare($this->clusterInfo->getServerVersion(), '7.0', '<')) {
                    $this->useDocumentType = true;
                }
            }
        }

        return $this->useDocumentType;
    }
}
