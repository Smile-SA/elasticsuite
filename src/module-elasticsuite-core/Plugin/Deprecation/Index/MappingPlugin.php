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
namespace Smile\ElasticsuiteCore\Plugin\Deprecation\Index;

use Smile\ElasticsuiteCore\Api\Index\MappingInterface;

/**
 * Implements backward compatibility of mapping definitions with ES 5.x.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class MappingPlugin
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
     * Disable the _all field only on ES <6 (this field does not exist anymore since ES 7.0.0).
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Smile\ElasticsuiteCore\Api\Index\MappingInterface $mapping The mapping
     * @param array                                              $result  The result
     *
     * @return mixed
     */
    public function afterAsArray(MappingInterface $mapping, $result)
    {
        if (strcmp($this->serverVersion, "6") < 0) {
            $result['_all'] = ['enabled' => false];
        }

        return $result;
    }
}
