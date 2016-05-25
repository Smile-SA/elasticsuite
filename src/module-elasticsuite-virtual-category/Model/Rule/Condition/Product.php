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
namespace Smile\ElasticSuiteVirtualCategory\Model\Rule\Condition;

/**
 * Product search rule condition.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Product extends \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product
{
    /**
     * Build a search query for the current rule.
     *
     * @param array $excludedCategories Categories excluded of query building (avoid infinite recursion).
     *
     * @return QueryInterface
     */
    public function getSearchQuery($excludedCategories = [])
    {
        $searchQuery = parent::getSearchQuery();

        if ($this->getAttribute() == "category_ids") {
        }

        return $searchQuery;
    }
}
