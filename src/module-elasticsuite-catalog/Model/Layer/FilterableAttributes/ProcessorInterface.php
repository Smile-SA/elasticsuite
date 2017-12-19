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
namespace Smile\ElasticsuiteCatalog\Model\Layer\FilterableAttributes;

use Magento\Catalog\Model\Layer\FilterableAttributeListInterface;

/**
 * Interface for filterable attributes provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface ProcessorInterface
{
    /**
     * Process filterable attributes list for a given layer.
     * This allow to reorder or filter them according to your needs.
     *
     * @param FilterableAttributeListInterface $filterableAttributeList Filterable Attribute List
     * @param \Magento\Catalog\Model\Layer     $layer                   The Layer
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    public function processFilterableAttributes(
        FilterableAttributeListInterface $filterableAttributeList,
        \Magento\Catalog\Model\Layer $layer
    );
}
