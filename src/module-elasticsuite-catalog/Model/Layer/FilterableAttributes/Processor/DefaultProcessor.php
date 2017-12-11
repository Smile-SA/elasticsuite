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
namespace Smile\ElasticsuiteCatalog\Model\Layer\FilterableAttributes\Processor;

use Magento\Catalog\Model\Layer\FilterableAttributeListInterface;
use Smile\ElasticsuiteCatalog\Model\Layer\FilterableAttributes\ProcessorInterface;

/**
 * Default Processor for Filterable Attributes.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class DefaultProcessor implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function processFilterableAttributes(
        FilterableAttributeListInterface $filterableAttributeList,
        \Magento\Catalog\Model\Layer $layer
    ) {
        return $filterableAttributeList->getList();
    }
}
