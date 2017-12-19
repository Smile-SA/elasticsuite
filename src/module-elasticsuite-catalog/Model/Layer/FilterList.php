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

use Smile\ElasticsuiteCatalog\Model\Layer\FilterableAttributes\ProcessorInterface as FilterableAttributesProcessorInterface;
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
     * @var array
     */
    private $relevantFilters = [];

    /**
     * @var FilterableAttributesProcessorInterface
     */
    private $filterableAttributesProcessor;

    /**
     * FilterList constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface                     $objectManager                 Object Manager
     * @param \Magento\Catalog\Model\Layer\FilterableAttributeListInterface $filterableAttributes          Filterable Attributes
     * @param CoverageRateProvider                                          $coverageRateProvider          Coverage Rate Provider
     * @param FilterableAttributesProcessorInterface                        $filterableAttributesProcessor Filterable attributes processor
     * @param array                                                         $filters                       Filters
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\Layer\FilterableAttributeListInterface $filterableAttributes,
        CoverageRateProvider $coverageRateProvider,
        FilterableAttributesProcessorInterface $filterableAttributesProcessor,
        array $filters = []
    ) {
        $this->coverageRateProvider          = $coverageRateProvider;
        $this->filterableAttributesProcessor = $filterableAttributesProcessor;
        parent::__construct($objectManager, $filterableAttributes, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(\Magento\Catalog\Model\Layer $layer)
    {
        if (!count($this->filters)) {
            $this->filters = [
                $this->objectManager->create($this->filterTypes[self::CATEGORY_FILTER], ['layer' => $layer]),
            ];

            $filterableAttributes = $this->filterableAttributesProcessor
                ->processFilterableAttributes($this->filterableAttributes, $layer);

            foreach ($filterableAttributes as $attribute) {
                $this->filters[] = $this->createAttributeFilter($attribute, $layer);
            }
        }

        return $this->filters;
    }

    /**
     * Retrieve only relevant filters : filters that have a coverage rate on current collection which is
     * greater than their configuration.
     *
     * @param \Magento\Catalog\Model\Layer $layer Navigation Layer
     *
     * @return array
     */
    public function getRelevantFilters(\Magento\Catalog\Model\Layer $layer)
    {
        if (!count($this->relevantFilters)) {
            $coverageRates = $this->coverageRateProvider->getCoverageRates($layer->getProductCollection());
            foreach ($this->getFilters($layer) as $filter) {
                try {
                    $attribute           = $filter->getAttributeModel();
                    $facetCoverageRate   = $attribute->getFacetMinCoverageRate();
                    $currentCoverageRate = $coverageRates[$attribute->getAttributeCode()] ?? 0;
                    if ($currentCoverageRate > $facetCoverageRate) {
                        $this->relevantFilters[] = $filter;
                    }
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    // Category Filter has no attribute model, which causes exception.
                    $this->relevantFilters[] = $filter;
                }
            }
        }

        return $this->relevantFilters;
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
            && isset($this->filterTypes[self::BOOLEAN_FILTER])
        ) {
            $filterClassName = $this->filterTypes[self::BOOLEAN_FILTER];
        }

        return $filterClassName;
    }
}
