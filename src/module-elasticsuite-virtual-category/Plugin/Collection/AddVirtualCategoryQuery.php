<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Plugin\Collection;

use Magento\Catalog\Model\Category;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection;
use Smile\ElasticsuiteVirtualCategory\Model\Category\Filter\Provider;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Product\CollectionFactory;

/**
 * Add virtual category query in product collection filters.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Pierre Gauthier <pigau@smile.fr>
 */
class AddVirtualCategoryQuery
{
    /**
     * @var Provider
     */
    private $filterProvider;

    /**
     * AddVirtualCategoryQuery constructor.
     *
     * @param Provider $filterProvider Filter prodivder.
     */
    public function __construct(Provider $filterProvider)
    {
        $this->filterProvider = $filterProvider;
    }

    /**
     * Add virtual category query in collection filters.
     *
     * @param Collection $subject  Product collection.
     * @param Category   $category Category.
     * @return array
     */
    public function beforeAddCategoryFilter(Collection $subject, Category $category)
    {
        $category = clone $category; // Do not apply side-effect on the category itself.

        if ($category && $category->getData('is_virtual_category')) {
            $query = $this->filterProvider->getQueryFilter($category);
            if ($query !== null) {
                $subject->addQueryFilter($query);
            }
            // This is where cloning is useful. Otherwise, we could have a category without id later in the call stack.
            $category->setId(null);
        }

        return [$category];
    }
}
