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
namespace Smile\ElasticsuiteCatalog\Model\Layer;

use Magento\Catalog\Model\Layer\FilterableAttributeListInterface;

/**
 * Interface for filterable attributes provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface FilterableAttributesProviderInterface
{
    /**
     * @param \Magento\Catalog\Model\Layer $layer The Layer
     *
     * @return FilterableAttributeListInterface
     */
    public function getFilterableAttributes(\Magento\Catalog\Model\Layer $layer);
}
