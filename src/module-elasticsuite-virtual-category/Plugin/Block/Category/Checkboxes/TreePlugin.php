<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Plugin\Block\Category\Checkboxes;

use Magento\Catalog\Block\Adminhtml\Category\Checkboxes\Tree;
use Magento\Framework\Data\Tree\Node;

/**
 * Load the paths of the selected categories in the initial tree of the rule conditions category chooser.
 *
 * The initial tree only contains the first levels of categories, and up to Magento 2.4.7, the chooser rebuilds
 * the condition value from the loaded checked categories only: a deeper selected category is lost on the first click.
 * Loading the selected categories' paths keeps them in the tree, checked.
 * Inert from Magento 2.4.9, where the initial tree is already built from the selected categories.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class TreePlugin
{
    /**
     * Register a root built from the selected categories' paths before the initial tree is built.
     *
     * @param Tree      $subject            Category checkboxes tree.
     * @param Node|null $parentNodeCategory Parent node when lazy-loading a branch, null for the initial tree.
     *
     * @return null
     */
    public function beforeGetRoot(Tree $subject, $parentNodeCategory = null)
    {
        if ($parentNodeCategory === null && !empty($subject->getCategoryIds())) {
            $subject->getRootByIds($subject->getCategoryIds());
        }

        return null;
    }
}
