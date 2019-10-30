<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\Aggregation;

/**
 * Boolean attribute aggregation
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class Boolean extends DefaultAttribute
{
    /**
     * {@inheritDoc}
     */
    protected function getFilterField(\Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute)
    {
        return $attribute->getAttributeCode();
    }
}
