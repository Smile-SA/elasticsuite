<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Plugin\Catalog\Category;

/**
 * Save the category product sorting at save time.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SaveFilterableAttributes
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute
     */
    private $saveHandler;

    /**
     * PrepareFilterableAttributeList constructor.
     *
     * @param \Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute $saveHandler Save Handler
     */
    public function __construct(\Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute $saveHandler)
    {
        $this->saveHandler = $saveHandler;
    }

    /**
     * Resource model save function plugin.
     * Append a commit callback to save the product positions.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\Catalog\Model\ResourceModel\Category $categoryResource Category original resource model.
     * @param \Closure                                      $proceed          Original save method.
     * @param \Magento\Framework\Model\AbstractModel        $category         Saved category.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category
     */
    public function aroundSave(
        \Magento\Catalog\Model\ResourceModel\Category $categoryResource,
        \Closure $proceed,
        \Magento\Framework\Model\AbstractModel $category
    ) {
        if ($category->getId() && $category->getFacetConfig() && $category->getFacetConfigOrder()) {
            $position  = [];
            $data      = $category->getFacetConfig();
            $sortOrder = $category->getFacetConfigOrder();

            foreach ($data as $key => &$item) {
                $item['position'] = isset($sortOrder[$key]) ? $sortOrder[$key] : $item['position'];
                $position[] = (int) $item['position'];
            }
            array_multisort($position, SORT_ASC, $data);

            $this->saveHandler->saveAttributesData($category->getId(), $data);
        }

        return $proceed($category);
    }
}
