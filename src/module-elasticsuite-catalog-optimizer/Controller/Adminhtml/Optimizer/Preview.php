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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Controller\Adminhtml\Optimizer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterfaceFactory;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\PreviewFactory;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\ContainerConfigurationFactory;

/**
 * Preview Controller for Optimizer
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @var \Magento\Search\Model\QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Search\ContextInterface
     */
    private $searchContext;

    /**
     * Constructor.
     *
     * @param Context                       $context                Controller  context.
     * @param PreviewFactory                $previewModelFactory    Preview model factory.
     * @param CategoryRepositoryInterface   $categoryRepository     Category Repository
     * @param OptimizerInterfaceFactory     $optimizerFactory       OptimzerFactory
     * @param ContainerConfigurationFactory $containerConfigFactory Container Configuration Factory
     * @param JsonHelper                    $jsonHelper             JSON Helper.
     * @param QueryFactory                  $queryFactory           Query Factory.
     * @param ContextInterface              $searchContext          Search context.
     */
    public function __construct(
        Context $context,
        PreviewFactory $previewModelFactory,
        CategoryRepositoryInterface $categoryRepository,
        OptimizerInterfaceFactory $optimizerFactory,
        ContainerConfigurationFactory $containerConfigFactory,
        JsonHelper $jsonHelper,
        QueryFactory $queryFactory,
        ContextInterface $searchContext
    ) {
        parent::__construct($context);

        $this->optimizerFactory       = $optimizerFactory;
        $this->categoryRepository     = $categoryRepository;
        $this->previewModelFactory    = $previewModelFactory;
        $this->containerConfigFactory = $containerConfigFactory;
        $this->jsonHelper             = $jsonHelper;
        $this->queryFactory           = $queryFactory;
        $this->searchContext          = $searchContext;
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

        $this->updateSearchContext($this->getStoreId(), $category, $queryText);

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

    /**
     * Update the search context using current store id, category or query text.
     *
     * @param integer           $storeId   Store id.
     * @param CategoryInterface $category  Category.
     * @param string            $queryText Fulltext query text.
     *
     * @return void
     */
    private function updateSearchContext($storeId, $category, $queryText)
    {
        $this->searchContext->setStoreId($storeId);

        if ((string) $queryText !== '') {
            try {
                $query = $this->queryFactory->create();
                $query->setStoreId($storeId);
                $query->loadByQueryText($queryText);

                if ($query->getId()) {
                    $this->searchContext->setCurrentSearchQuery($query);
                }
            } catch (\Magento\Framework\Exception\LocalizedException $exception) {
                // Do not break if we fail to retrieve the query.
            }
        } elseif ($category && $category->getId()) {
            $this->searchContext->setCurrentCategory($category);
        }
    }
}
