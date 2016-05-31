<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteVirtualCategory\Controller\Adminhtml\Category\Virtual;

use Magento\Backend\App\Action;
use Magento\Catalog\Api\Data\CategoryInterface;

/**
 * Virtual category preview controller.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Preview extends Action
{
    /**
     * @var \Smile\ElasticSuiteVirtualCategory\Model\PreviewFactory
     */
    private $previewModelFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * Constructor.
     *
     * @param \Magento\Backend\App\Action\Context                     $context             Controller context.
     * @param \Smile\ElasticSuiteVirtualCategory\Model\PreviewFactory $previewModelFactory Preview model factory.
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface        $categoryRepository  Category repository.
     * @param \Magento\Framework\Json\Helper\Data                     $jsonHelper          JSON Helper.
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Smile\ElasticSuiteVirtualCategory\Model\PreviewFactory $previewModelFactory,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        parent::__construct($context);

        $this->categoryRepository  = $categoryRepository;
        $this->previewModelFactory = $previewModelFactory;
        $this->jsonHelper          = $jsonHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $category = $this->getCategory();
        $responseObject = $this->previewModelFactory->create(['category' => $category])->getData();
        $json = $this->jsonHelper->jsonEncode($responseObject);

        $this->getResponse()->representJson($json);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * {@inheritDoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Catalog::categories');
    }

    /**
     * Load current category and apply admin current modifications (added and removed products, updated virtual rule, ...).
     *
     * @return CategoryInterface
     */
    private function getCategory()
    {
        $storeId  = $this->getRequest()->getParam('store');
        $category = $this->categoryRepository->get($this->getRequest()->getParam('id'), $storeId);

        $categoryProductIds = $this->jsonHelper->jsonDecode($this->getRequest()->getParam('category_products'));
        $category->setProductIds(array_keys($categoryProductIds));

        $categoryPostData = $this->getRequest()->getParam('general', []);

        $isVirtualCategory = isset($categoryPostData['is_virtual_category']) ? (bool) $categoryPostData['is_virtual_category'] : false;

        if ($isVirtualCategory) {
            $category->setIsVirtualCategory($isVirtualCategory);
            $category->getVirtualRule()->loadPost($categoryPostData['virtual_rule']);
            $category->setVirtualCategoryRoot($categoryPostData['virtual_category_root']);
        }

        return $category;
    }
}
