<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Api\Data;

use Magento\Catalog\Api\Data\CategoryInterface;

/**
 * Virtual Category Rule Interface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface VirtualRuleInterface
{
    /**
     * Build search query by category.
     *
     * @param CategoryInterface $category           Search category.
     * @param array             $excludedCategories Categories that should not be used into search query building.
     *                                              Used to avoid infinite recursion while building virtual categories rules.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    public function getCategorySearchQuery($category, $excludedCategories = []);

    /**
     * Retrieve search queries of children categories.
     *
     * @param CategoryInterface $rootCategory Root category.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface[]
     */
    public function getSearchQueriesByChildren(CategoryInterface $rootCategory);
}
