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
namespace Smile\ElasticsuiteCatalog\Model\Layer\FilterableAttributesProvider;

use Magento\Catalog\Model\Layer\FilterableAttributeListInterface;
use Smile\ElasticsuiteCatalog\Model\Layer\FilterableAttributesProviderInterface;

/**
 * Default Provider for Filterable Attributes.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class DefaultProvider implements FilterableAttributesProviderInterface
{
    /**
     * @var \Magento\Catalog\Model\Layer\FilterableAttributeListInterface
     */
    private $filterableAttributesList;

    /**
     * DefaultProvider constructor.
     *
     * @param \Magento\Catalog\Model\Layer\FilterableAttributeListInterface $filterableAttributeList Filterable Attributes List
     */
    public function __construct(FilterableAttributeListInterface $filterableAttributeList)
    {
        $this->filterableAttributesList = $filterableAttributeList;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterableAttributes(\Magento\Catalog\Model\Layer $layer)
    {
        return $this->filterableAttributesList;
    }
}
