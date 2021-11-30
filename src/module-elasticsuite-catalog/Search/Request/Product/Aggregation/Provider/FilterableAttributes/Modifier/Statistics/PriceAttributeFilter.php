<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\Modifier\Statistics;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

/**
 * Class PriceAttributeFilter
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class PriceAttributeFilter implements AttributeFilterInterface
{
    /**
     * {@inheritDoc}
     */
    public function filterAttribute(Attribute $attribute): bool
    {
        if ($attribute->getAttributeCode() == 'price') {
            return true;
        }

        return false;
    }
}
