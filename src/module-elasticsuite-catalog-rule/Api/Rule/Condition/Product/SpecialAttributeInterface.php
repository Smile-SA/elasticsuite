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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Api\Rule\Condition\Product;

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
     * @return string
     */
    public function getAttributeCode();

    /**
     * @return QueryInterface
     */
    public function getSearchQuery();

    /**
     * @return string
     */
    public function getOperatorName();

    /**
     * @return string
     */
    public function getInputType();

    /**
     * @return string
     */
    public function getValueElementType();

    /**
     * @return string
     */
    public function getValueName();

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @return array
     */
    public function getValueOptions();

    /**
     * @return string
     */
    public function getLabel();
}
