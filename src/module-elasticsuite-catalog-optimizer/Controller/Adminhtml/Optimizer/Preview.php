<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Controller\Adminhtml\Optimizer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterfaceFactory;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\PreviewFactory;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\ContainerConfigurationFactory;

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
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\ContainerConfigurationFactory
     */
    private $containerConfigFactory;

    /**
     * Constructor.
     *
     * @param Context                       $context                Controller  context.
     * @param PreviewFactory                $previewModelFactory    Preview model factory.
     * @param CategoryRepositoryInterface   $categoryRepository     Category Repository
     * @param OptimizerInterfaceFactory     $optimizerFactory       OptimzerFactory
     * @param ContainerConfigurationFactory $containerConfigFactory Container Configuration Factory
     * @param JsonHelper                    $jsonHelper             JSON Helper.
     */
    public function __construct(
        Context $context,
        PreviewFactory $previewModelFactory,
        CategoryRepositoryInterface $categoryRepository,
        OptimizerInterfaceFactory $optimizerFactory,
        ContainerConfigurationFactory $containerConfigFactory,
        JsonHelper $jsonHelper
    ) {
        parent::__construct($context);

        $this->optimizerFactory       = $optimizerFactory;
        $this->categoryRepository     = $categoryRepository;
        $this->previewModelFactory    = $previewModelFactory;
        $this->containerConfigFactory = $containerConfigFactory;
        $this->jsonHelper             = $jsonHelper;
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
        $optimizer       = $this->getOptimizer();
        $pageSize        = $this->getPageSize();
        $queryText       = $this->getQueryText();
        $category        = $this->getCategory();
        $containerConfig = $this->getContainerConfiguration();

        return $this->previewModelFactory->create(
            [
                'optimizer'       => $optimizer,
                'containerConfig' => $containerConfig,
                'category'        => $category,
                'queryText'       => $queryText,
                'size'            => $pageSize,
            ]
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
     * Load current category using the request params.
     *
     * @return CategoryInterface
     */
    private function getCategory()
    {
        $storeId    = $this->getStoreId();
        $categoryId = $this->getCategoryId();
        $category   = null;

        if ($this->getCategoryId()) {
            $category = $this->categoryRepository->get($categoryId, $storeId);
        }

        return $category;
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
     * Return the container to preview.
     *
     * @return ContainerConfigurationInterface
     */
    private function getContainerConfiguration()
    {
        $containerName = $this->getRequest()->getParam('search_container_preview');

        $containerConfig = $this->containerConfigFactory->create(
            ['containerName' => $containerName, 'storeId' => $this->getOptimizer()->getStoreId()]
        );

        return $containerConfig;
    }

    /**
     * Return the query text to preview.
     *
     * @return string
     */
    private function getQueryText()
    {
        $queryText = trim(strtolower((string) $this->getRequest()->getParam('query_text_preview', '')));

        if ($queryText == '') {
            $queryText = null;
        }

        return $queryText;
    }

    /**
     * Return the category to preview.
     *
     * @return int
     */
    private function getCategoryId()
    {
        return $this->getRequest()->getParam('category_preview');
    }

    /**
     * Return the store id to preview.
     *
     * @return int
     */
    private function getStoreId()
    {
        return $this->getRequest()->getParam('store_id');
    }
}
