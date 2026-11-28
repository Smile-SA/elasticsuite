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
 *
 * Tree::getRootByIds() can only run once per block: it adds the tree data to the block's cached category
 * collection, joining url_rewrite on it every time ("A joint field with a "0" alias is already declared."
 * on the second call). getRoot() is called several times while rendering the chooser, and Magento 2.4.9
 * calls getRootByIds() itself from getTree()/getTreeJson(), so the paths are loaded once and the cached
 * collection is released for the next call.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class TreePlugin
{
    /**
     * Block data flag: the selected categories' paths are already loaded.
     */
    const SELECTED_PATHS_LOADED_FLAG = 'elasticsuite_selected_paths_loaded';

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
        if ($parentNodeCategory === null
            && !empty($subject->getCategoryIds())
            && !$subject->getData(self::SELECTED_PATHS_LOADED_FLAG)
        ) {
            $subject->setData(self::SELECTED_PATHS_LOADED_FLAG, true);
            $subject->getRootByIds($subject->getCategoryIds());
            // The url_rewrite join is now on the cached collection: let the next getRootByIds() start from a new one.
            $subject->unsetData('category_collection');
        }

        return null;
    }
}
