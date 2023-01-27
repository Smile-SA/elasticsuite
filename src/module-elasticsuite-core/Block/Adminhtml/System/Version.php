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
     * @param \Magento\Backend\Block\Template\Context       $context         Block context
     * @param \Smile\ElasticsuiteCore\Model\ProductMetadata $productMetadata Product Metadata
     * @param array                                         $data            Block data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Smile\ElasticsuiteCore\Model\ProductMetadata $productMetadata,
        array $data = []
    ) {
        $this->productMetadata = $productMetadata;
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
