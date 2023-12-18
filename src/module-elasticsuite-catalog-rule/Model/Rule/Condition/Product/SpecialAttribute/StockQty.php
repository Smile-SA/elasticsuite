<?php
/**
 * Elasticsuite stock qty catalog rule special attribute provider.
 * Allows to use the stock qty (self or those of children for configurable products) to build catalog rules for
 * search optimizers or virtual categories rules.
 * Please note that indexed stock.qty for configurable or bundle products will vary depending on your usage
 * of Multi Source Inventory modules: it will be left at 0 if you use the default source and stock.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute;

use Smile\ElasticsuiteCatalogRule\Api\Rule\Condition\Product\SpecialAttributeInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product as ProductCondition;

/**
 * Class StockQty
 *
 * @category Elasticsuite
 * @package  Elasticsuite\CatalogRule
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class StockQty implements SpecialAttributeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAttributeCode()
    {
        return 'stock.qty';
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSearchQuery(ProductCondition $condition)
    {
        // Query can be computed directly with the attribute code and value. (eg. stock.qty < 5).
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperatorName()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputType()
    {
        return 'numeric';
    }

    /**
     * {@inheritdoc}
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function getValueName($value)
    {
        if ($value === null || '' === $value) {
            return '...';
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($rawValue)
    {
        return $rawValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueOptions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __('Stock qty');
    }
}
