<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Api\Layer\Filter;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

/**
 * Filter type provider interface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
interface TypeProviderInterface
{
    /**
     * Return filter class name.
     *
     * @param Attribute $attribute               The attribute model.
     * @param string    $originalFilterClassName The original/default filter class name.
     *
     * @return string
     */
    public function getFilterClassName(Attribute $attribute, string $originalFilterClassName): string;
}
