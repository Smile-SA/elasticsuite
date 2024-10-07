<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Plugin\Catalog\Category;

/**
 * Plugin on Catalog Category Widget Chooser controller.
 *
 * Used to change the block class used when editing virtual category rule.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ChooserPlugin
{
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    private $layout;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * ChooserPlugin constructor.
     *
     * @param \Magento\Framework\View\LayoutInterface          $layoutInterface    Layout
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository Category Repository
     */
    public function __construct(
        \Magento\Framework\View\LayoutInterface $layoutInterface,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
    ) {
        $this->layout = $layoutInterface;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Plugin on execute() function of the category chooser controller.
     * Done via a plugin instead of layout because legacy controller is already creating block via getLayout()->createBlock().
     *
     * @param \Magento\CatalogRule\Controller\Adminhtml\Promo\Widget\Chooser $controller The controller
     * @param \Closure                                                       $proceed    The execute() function
     *
     * @return mixed
     */
    public function aroundExecute(
        \Magento\CatalogRule\Controller\Adminhtml\Promo\Widget\Chooser $controller,
        \Closure $proceed
    ) {
        // Exit if we are not building a category chooser.
        if ($controller->getRequest()->getParam('attribute') !== 'category_ids') {
            return $proceed();
        }

        // Fallback to legacy action if there is no category context.
        if ($controller->getRequest()->getParam('category_id') === null) {
            return $proceed();
        }

        // Retrieve current category.
        $category = $this->categoryRepository->get($controller->getRequest()->getParam('category_id'));

        $block = $this->layout->createBlock(
            \Smile\ElasticsuiteVirtualCategory\Block\Adminhtml\Catalog\Category\Checkboxes\Tree::class,
            'promo_widget_chooser_category_ids',
            [
                'data' => [
                    'js_form_object'   => $controller->getRequest()->getParam('form'),
                    'current_category' => $category,
                ],
            ]
        )->setCategoryIds(
            $this->getIds($controller)
        );

        return $controller->getResponse()->setBody($block->toHtml());
    }

    /**
     * Retrieve currently selected Ids.
     *
     * @param \Magento\Backend\App\Action $controller The controller
     *
     * @return array
     */
    private function getIds($controller)
    {
        $ids = $controller->getRequest()->getParam('selected', []);

        if (!is_array($ids)) {
            $ids = [];
        }

        foreach ($ids as $key => &$categoryId) {
            $categoryId = (int) $categoryId;
            if ($categoryId <= 0) {
                unset($ids[$key]);
            }
        }

        return array_unique($ids);
    }
}
