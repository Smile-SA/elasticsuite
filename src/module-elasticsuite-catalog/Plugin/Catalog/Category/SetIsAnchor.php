<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Catalog\Category;

/**
 * Plugin on Category Resource model to ensure category is set to is_anchor=1 before saving.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SetIsAnchor
{
    /**
     * Resource model save function plugin.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\Catalog\Model\ResourceModel\Category $categoryResource Category original resource model.
     * @param \Closure                                      $proceed          Original save method.
     * @param \Magento\Framework\Model\AbstractModel        $category         Saved category.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category
     * @throws \Exception
     */
    public function aroundSave(
        \Magento\Catalog\Model\ResourceModel\Category $categoryResource,
        \Closure $proceed,
        \Magento\Framework\Model\AbstractModel $category
    ) {
        $category->setData('is_anchor', true);

        return $proceed($category);
    }
}
