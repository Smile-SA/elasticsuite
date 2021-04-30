<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Aurelien FOUCRET <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Api\Rule\Condition\Product;

use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product as ProductCondition;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Definition of "special attributes" that can be used for building rules with Elasticsuite.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface SpecialAttributeInterface
{
    /**
     * Return attribute code/field name.
     *
     * @return string
     */
    public function getAttributeCode();

    /**
     * Return complex search query if applicable.
     *
     * @param ProductCondition $condition Product condition.
     *
     * @return QueryInterface|null
     */
    public function getSearchQuery(ProductCondition $condition);

    /**
     * Get operator name.
     * Return null to let the native code determine the operator name according to the operator.
     *
     * @return string|null
     */
    public function getOperatorName();

    /**
     * This value will define which operators will be available for this condition.
     * Possible values are: string, numeric, date, select, multiselect, grid, boolean.
     *
     * @return string
     */
    public function getInputType();

    /**
     * Value element type will define renderer for condition value element.
     *
     * @return string
     */
    public function getValueElementType();

    /**
     * Get value name.
     *
     * @param mixed $value Raw condition value.
     *
     * @return array|string
     */
    public function getValueName($value);

    /**
     * Get value.
     * Allows to hardcode a value for boolean special attributes.
     *
     * @param mixed $value Raw condition value.
     *
     * @return mixed
     */
    public function getValue($value);

    /**
     * Get value options.
     *
     * @return array
     */
    public function getValueOptions();

    /**
     * Get label.
     *
     * @return string
     */
    public function getLabel();
}
