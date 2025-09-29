<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Controller\Adminhtml\Category\Virtual;

use Magento\Backend\App\Action;
use Magento\Catalog\Api\Data\CategoryInterface;

/**
 * Virtual category preview controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Preview extends Action
{
    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\PreviewFactory
     */
    private $previewModelFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $categoryFactory;

    /**
     * Constructor.
     *
     * @param \Magento\Backend\App\Action\Context                     $context             Controller context.
     * @param \Smile\ElasticsuiteVirtualCategory\Model\PreviewFactory $previewModelFactory Preview model factory.
     * @param \Magento\Catalog\Model\CategoryFactory                  $categoryFactory     Category factory.
     * @param \Magento\Framework\Json\Helper\Data                     $jsonHelper          JSON Helper.
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Smile\ElasticsuiteVirtualCategory\Model\PreviewFactory $previewModelFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        parent::__construct($context);

        $this->categoryFactory     = $categoryFactory;
        $this->previewModelFactory = $previewModelFactory;
        $this->jsonHelper          = $jsonHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $responseData = $this->getPreviewObject()->getData();
        $json = $this->jsonHelper->jsonEncode($responseData);

        return $this->getResponse()->representJson($json);
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
     * Load and initialize the preview model.
     *
     * @return \Smile\ElasticsuiteVirtualCategory\Model\Preview
     */
    private function getPreviewObject()
    {
        $category = $this->getCategory();
        $pageSize = $this->getPageSize();
        $search = $this->getRequest()->getParam('search');

        return $this->previewModelFactory->create(['category' => $category, 'size' => $pageSize, 'search' => $search]);
    }

    /**
     * Load current category and apply admin current modifications (added and removed products, updated virtual rule, ...).
     *
     * @return CategoryInterface
     */
    private function getCategory()
    {
        $category = $this->loadCategory();

        $this->addVirtualCategoryData($category)
            ->addSelectedProducts($category)
            ->setSortedProducts($category);

        return $category;
    }

    /**
     * Load current category using the request params.
     *
     * @return CategoryInterface
     */
    private function loadCategory()
    {
        $category   = $this->categoryFactory->create();
        $storeId    = $this->getRequest()->getParam('store');
        $categoryId = $this->getRequest()->getParam('entity_id');

        $category->setStoreId($storeId)->load($categoryId);

        return $category;
    }

    /**
     * Append virtual rule params to the category.
     *
     * @param CategoryInterface $category Category.
     *
     * @return $this
     */
    private function addVirtualCategoryData(CategoryInterface $category)
    {
        $originalData = [
            'is_virtual_category' => (bool) $category->getOrigData('is_virtual_category'),
            'virtual_rule' => "{$category->getOrigData('virtual_rule')}",
            'virtual_category_root' => $category->getOrigData('virtual_category_root'),
        ];

        $isVirtualCategory = (bool) $this->getRequest()->getParam('is_virtual_category');
        $category->setIsVirtualCategory($isVirtualCategory);

        if ($isVirtualCategory) {
            $category->getVirtualRule()->loadPost($this->getRequest()->getParam('virtual_rule', []));
            $category->setVirtualCategoryRoot($this->getRequest()->getParam('virtual_category_root', null));
        }

        $newData = [
            'is_virtual_category' => $category->getData('is_virtual_category'),
            'virtual_rule' => "{$category->getData('virtual_rule')}",
            'virtual_category_root' => $category->getData('virtual_category_root'),
        ];

        $category->setData('has_draft_virtual_rule', !empty(array_diff($originalData, $newData)));

        return $this;
    }

    /**
     * Add user selected products.
     *
     * @param CategoryInterface $category Category.
     *
     * @return $this
     */
    private function addSelectedProducts(CategoryInterface $category)
    {
        $selectedProducts = $this->getRequest()->getParam('selected_products', []);

        $addedProducts = isset($selectedProducts['added_products']) ? $selectedProducts['added_products'] : [];
        $category->setAddedProductIds($addedProducts);

        $deletedProducts = isset($selectedProducts['deleted_products']) ? $selectedProducts['deleted_products'] : [];
        $category->setDeletedProductIds($deletedProducts);

        return $this;
    }

    /**
     * Append products sorted by the user to the category.
     *
     * @param CategoryInterface $category Category.
     *
     * @return $this
     */
    private function setSortedProducts(CategoryInterface $category)
    {
        $productPositions = $this->getRequest()->getParam('product_position', []);
        $category->setSortedProductIds(array_keys($productPositions));

        return $this;
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
}
