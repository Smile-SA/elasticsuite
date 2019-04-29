<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Allow to apply automatic filters on nested field during rule query conversion.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface NestedFilterInterface
{
    /**
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    public function getFilter();
}
