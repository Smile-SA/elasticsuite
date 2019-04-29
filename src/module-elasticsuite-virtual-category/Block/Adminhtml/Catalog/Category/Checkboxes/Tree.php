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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Block\Adminhtml\Catalog\Category\Checkboxes;

/**
 * Custom Categories checkboxes tree.
 *
 * Extends the legacy block to be able to limit the displayed tree to the current category upper root.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Tree extends \Magento\Catalog\Block\Adminhtml\Category\Checkboxes\Tree
{
    /**
     * {@inheritdoc}
     *
     * Overridden to load only Root of the current category if needed.
     */
    public function getRoot($parentNodeCategory = null, $recursionLevel = 3)
    {
        if ($parentNodeCategory === null) {
            if ($this->getCurrentCategoryId()) {
                $this->getRootByIds([$this->getCurrentCategoryId()]);
            }
        }

        return parent::getRoot($parentNodeCategory, $recursionLevel);
    }

    /**
     * {@inheritdoc}
     *
     * Overridden to exclude other trees than the current category.
     */
    public function getCategoryCollection()
    {
        $collection = parent::getCategoryCollection();

        // Previous call to $this->getRootByIds() will exclude all other category trees but keep their root.
        // Now we exclude all root categories except root associated to the current one.
        if ($this->getCurrentCategory()) {
            $rootPath      = array_slice($this->getCurrentCategory()->getPathIds(), 0, 2);
            $pathCondition = implode('/', $rootPath) . '%';
            $collection->addFieldToFilter('path', ['like' => $pathCondition]);

            // We also exclude current category, to prevent inception.
            $collection->addFieldToFilter('entity_id', ['neq' => $this->getCurrentCategory()->getId()]);

            // Overwrite collection already set in parent call.
            $this->setData('category_collection', $collection);
        }

        return $collection;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('Magento_Catalog::catalog/category/checkboxes/tree.phtml');
    }
}
