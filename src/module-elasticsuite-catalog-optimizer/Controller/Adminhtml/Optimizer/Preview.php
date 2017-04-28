<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Controller\Adminhtml\Optimizer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterfaceFactory;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\PreviewFactory;

/**
 * Preview Controller for Optimizer
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Preview extends Action
{
    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\PreviewFactory
     */
    private $previewModelFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var OptimizerInterfaceFactory
     */
    private $optimizerFactory;

    /**
     * Constructor.
     *
     * @param Context                   $context             Controller  context.
     * @param PreviewFactory            $previewModelFactory Preview model factory.
     * @param OptimizerInterfaceFactory $optimizerFactory    OptimzerFactory
     * @param JsonHelper                $jsonHelper          JSON Helper.
     */
    public function __construct(
        Context $context,
        PreviewFactory $previewModelFactory,
        OptimizerInterfaceFactory $optimizerFactory,
        JsonHelper $jsonHelper
    ) {
        parent::__construct($context);

        $this->optimizerFactory    = $optimizerFactory;
        $this->previewModelFactory = $previewModelFactory;
        $this->jsonHelper          = $jsonHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $responseData = $this->getPreviewObject()->getData();
        $json         = $this->jsonHelper->jsonEncode($responseData);

        $this->getResponse()->representJson($json);
    }

    /**
     * Check if allowed to manage optimizer.
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Smile_ElasticsuiteCatalogOptimizer::manage');
    }

    /**
     * Load and initialize the preview model.
     *
     * @return \Smile\ElasticsuiteVirtualCategory\Model\Preview
     */
    private function getPreviewObject()
    {
        $optimizer = $this->getOptimizer();
        $pageSize  = $this->getPageSize();
        $queryText = $this->getQueryText();

        return $this->previewModelFactory->create(
            ['optimizer' => $optimizer, 'size' => $pageSize, 'queryText' => $queryText]
        );
    }

    /**
     * Load current category and apply admin current modifications (added and removed products, updated virtual rule, ...).
     *
     * @return OptimizerInterface
     */
    private function getOptimizer()
    {
        $optimizer = $this->optimizerFactory->create();
        $optimizer->setData($this->getRequest()->getPostValue());
        $ruleConditionPost = $this->getRequest()->getParam('rule_condition', []);
        $optimizer->getRuleCondition()->loadPost($ruleConditionPost);

        return $optimizer;
    }

    /**
     * Return the preview page size.
     *
     * @return int
     */
    private function getPageSize()
    {
        return (int) $this->getRequest()->getParam('page_size');
    }

    /**
     * Return the preview page size.
     *
     * @return int
     */
    private function getQueryText()
    {
        $previewParam = $this->getRequest()->getParam('optimizer_preview', []);

        return isset($previewParam['queryText']) ? $previewParam['queryText'] : '';
    }
}
