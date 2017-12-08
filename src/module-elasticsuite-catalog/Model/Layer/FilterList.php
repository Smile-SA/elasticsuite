<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Layer;

use Smile\ElasticsuiteCatalog\Model\Product\Attribute\CoverageRateProvider;

/**
 * FilterList customization to support decimal filters.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class FilterList extends \Magento\Catalog\Model\Layer\FilterList
{
    /**
     * Boolean filter name
     */
    const BOOLEAN_FILTER = 'boolean';

    /**
     * @var CoverageRateProvider
     */
    private $coverageRateProvider;

    /**
     * FilterList constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface                     $objectManager        Object Manager
     * @param \Magento\Catalog\Model\Layer\FilterableAttributeListInterface $filterableAttributes Filterable Attributes
     * @param CoverageRateProvider                                          $coverageRateProvider Coverage Rate Provider
     * @param array                                                         $filters              Filters
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\Layer\FilterableAttributeListInterface $filterableAttributes,
        CoverageRateProvider $coverageRateProvider,
        array $filters = []
    ) {
        $this->coverageRateProvider = $coverageRateProvider;
        parent::__construct($objectManager, $filterableAttributes, $filters);
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters(\Magento\Catalog\Model\Layer $layer)
    {
        if (!count($this->filters)) {
            parent::getFilters($layer);

            $coverageRates = $this->coverageRateProvider->getCoverageRates($layer->getProductCollection());
            foreach ($this->filters as $key => $filter) {
                try {
                    $attribute           = $filter->getAttributeModel();
                    $facetCoverageRate   = $attribute->getFacetMinCoverageRate();
                    $currentCoverageRate = $coverageRates[$attribute->getAttributeCode()] ?? 0;

                    if ($currentCoverageRate < $facetCoverageRate) {
                        unset($this->filters[$key]);
                    }
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    // Category Filter has no attribute model, which causes exception.
                }
            }
        }

        return $this->filters;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAttributeFilterClass(\Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute)
    {
        $filterClassName = parent::getAttributeFilterClass($attribute);

        if ($attribute->getBackendType() == 'varchar' && $attribute->getFrontendClass() == 'validate-number') {
            $filterClassName = $this->filterTypes[self::DECIMAL_FILTER];
        }

        if (($attribute->getFrontendInput() == 'boolean')
            && ($attribute->getSourceModel() == 'Magento\Eav\Model\Entity\Attribute\Source\Boolean')
            && isset($this->filterTypes[self::BOOLEAN_FILTER])) {
            $filterClassName = $this->filterTypes[self::BOOLEAN_FILTER];
        }

        return $filterClassName;
    }
}
