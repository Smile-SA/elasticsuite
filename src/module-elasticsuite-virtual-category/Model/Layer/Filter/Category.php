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

namespace Smile\ElasticSuiteVirtualCategory\Model\Layer\Filter;

use Smile\ElasticSuiteCore\Search\Request\BucketInterface;

/**
 * Product category filter implementation using virtual categories.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Category extends \Smile\ElasticSuiteCatalog\Model\Layer\Filter\Category
{
    protected function applyCategoryFilterToCollection(\Magento\Catalog\Api\Data\CategoryInterface $category)
    {
        var_dump($category->getVirtualRule()->getSearchQuery());
        return $this;
    }
}