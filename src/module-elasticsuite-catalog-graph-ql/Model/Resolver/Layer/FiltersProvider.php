<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogGraphQl
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogGraphQl\Model\Resolver\Layer;

use Magento\Catalog\Model\Layer\FilterListFactory;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\CatalogGraphQl\Model\Layer\Context;
use Magento\CatalogGraphQl\Model\Resolver\Layer\FilterableAttributesListFactory;
use Smile\ElasticsuiteCatalog\Model\Layer\FilterList;

/**
 * Custom Layer filters provider for GraphQL
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class FiltersProvider extends \Magento\CatalogGraphQl\Model\Resolver\Layer\FiltersProvider
{
    /**
     * @var \Magento\CatalogGraphQl\Model\Layer\Context
     */
    private $layerContext;

    /**
     * @var Resolver
     */
    private $layerResolver;

    /**
     * @var FilterList[]
     */
    private $filtersList = [];

    /**
     * @var FilterableAttributesListFactory
     */
    private $filterableAttributesListFactory;

    /**
     * @var FilterListFactory
     */
    private $filterListFactory;

    /**
     * @param Context                         $layerContext                    Layer Context
     * @param Resolver                        $layerResolver                   Layer Resolver
     * @param FilterableAttributesListFactory $filterableAttributesListFactory Filterable Attributes List
     * @param FilterListFactory               $filterListFactory               Filter List Factory
     * @param array                           $filtersList                     Filters List
     */
    public function __construct(
        Context $layerContext,
        Resolver $layerResolver,
        FilterableAttributesListFactory $filterableAttributesListFactory,
        FilterListFactory $filterListFactory,
        $filtersList = []
    ) {
        $this->layerContext                    = $layerContext;
        $this->layerResolver                   = $layerResolver;
        $this->filterableAttributesListFactory = $filterableAttributesListFactory;
        $this->filterListFactory               = $filterListFactory;
        $this->filtersList                     = $filtersList;
        parent::__construct($layerResolver, $filterableAttributesListFactory, $filterListFactory);
    }

    /**
     * Get layer type filters.
     *
     * @param string $layerType The layer type
     *
     * @return array
     */
    public function getFilters(string $layerType): array
    {
        $layer = $this->layerResolver->get();
        $layer->getProductCollection()->setPageSize(0);

        if (isset($this->filtersList[$layerType])) {
            $filterList = $this->filtersList[$layerType];

            return $filterList->getFilters($layer);
        }

        return parent::getFilters($layerType);
    }
}
