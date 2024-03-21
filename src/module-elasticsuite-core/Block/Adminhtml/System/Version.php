<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Block\Adminhtml\System;

use Magento\Backend\Block\Template\Context;
use Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface;
use Smile\ElasticsuiteCore\Model\ProductMetadata;

/**
 * Version block to display in footer.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName) The property _template is inherited
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Version extends \Magento\Backend\Block\Template
{
    /**
     * @var string
     */
    protected $_template = 'Smile_ElasticsuiteCore::system/version.phtml';

    /**
     * @var \Smile\ElasticsuiteCore\Model\ProductMetadata
     */
    protected $productMetadata;

    /**
     * @var ClusterInfoInterface
     */
    protected $clusterInfo;

    /**
     * @var array
     */
    protected $serverInfo;

    /**
     * @param Context              $context         Block context
     * @param ProductMetadata      $productMetadata Product Metadata
     * @param ClusterInfoInterface $clusterInfo     Elasticsearch/OpenSearch cluster information
     * @param array                $data            Block data
     */
    public function __construct(
        Context $context,
        ProductMetadata $productMetadata,
        ClusterInfoInterface $clusterInfo,
        array $data = []
    ) {
        $this->productMetadata = $productMetadata;
        $this->clusterInfo = $clusterInfo;
        parent::__construct($context, $data);
    }

    /**
     * Get Elasticsuite product version
     *
     * @return string
     */
    public function getElasticsuiteVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Get Elasticsuite product edition
     *
     * @return string
     */
    public function getElasticsuiteEdition()
    {
        return $this->productMetadata->getEdition();
    }

    /**
     * Get server distribution (Elasticsearch or OpenSearch)
     *
     * @return string
     */
    public function getServerDistribution()
    {
        try {
            $serverDistribution = $this->clusterInfo->getServerDistribution();
        } catch (\Exception $e) {
            $serverDistribution = 'Unknown';
        }

        return $serverDistribution;
    }

    /**
     * Get server version
     *
     * @return string
     */
    public function getServerVersion()
    {
        try {
            $serverVersion = $this->clusterInfo->getServerVersion();
        } catch (\Exception $e) {
            $serverVersion = 'Unknown';
        }

        return $serverVersion;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        $this->setShowProfiler(true);
    }

    /**
     * {@inheritDoc}
     */
    protected function getCacheLifetime()
    {
        return 3600 * 24 * 10;
    }
}
