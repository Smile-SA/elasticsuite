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
namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute;

use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteCatalog\Model\Category\FilterableAttribute\Source\DisplayMode;

/**
 * Filterable Attribute Collection.
 * Can be retrieved for a given category.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
{
    /**
     * Compute attributes proper filter and sort order for a given category.
     *
     * @param CategoryInterface $category The category
     *
     * @return $this
     */
    public function setCategoryFilter(CategoryInterface $category)
    {
        $joinCondition = [
            'fal.attribute_id = main_table.attribute_id',
            $this->getConnection()->quoteInto('fal.entity_id = ?', (int) $category->getId()),
        ];

        // Joining left allow to fallback on default attribute configuration if nothing is defined for this category.
        $this->joinLeft(
            ['fal' => $this->getTable('smile_elasticsuitecatalog_category_filterable_attribute')],
            new \Zend_Db_Expr(implode(' AND ', $joinCondition)),
            []
        );

        // Compute the position from the category_filterable_attribute table if they exists.
        $positionExpr    = sprintf('COALESCE(fal.position, NULLIF(additional_table.position, 0), %s)', PHP_INT_MAX);
        // Same thing for the display_mode : will default to the 'auto' value if not set for the given category.
        $displayModeExpr = sprintf('COALESCE(fal.display_mode, %s)', DisplayMode::AUTO_DISPLAYED);
        // Compute min coverage dynamically. Set it to 0 if display mode is set to always displayed.
        $coverageExpr    = sprintf('IF(display_mode=%s, 0, facet_min_coverage_rate)', DisplayMode::ALWAYS_DISPLAYED);

        $this->getSelect()->columns([
            'position'                => new \Zend_Db_Expr($positionExpr),
            'display_mode'            => new \Zend_Db_Expr($displayModeExpr),
            'facet_min_coverage_rate' => new \Zend_Db_Expr($coverageExpr),
        ]);

        return $this;
    }

    /**
     * Exclude attributes set to "always hidden".
     *
     * @return $this
     */
    public function filterHiddenAttributes()
    {
        $displayModeExpr = sprintf('display_mode IS NULL OR display_mode != %s', DisplayMode::ALWAYS_HIDDEN);
        $this->getSelect()->where(new \Zend_Db_Expr($displayModeExpr));

        return $this;
    }
}
