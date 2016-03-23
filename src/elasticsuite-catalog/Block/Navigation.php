<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCatalog\Block;

/**
 * Custom implementation of the navigation block to apply facet coverage rate.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Navigation extends \Magento\LayeredNavigation\Block\Navigation
{
    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->addFacets();

        return $this;
    }

    /**
     * Append facets to the search requests using the coverage rate defined in admin.
     *
     * @return void
     */
    private function addFacets()
    {
        $productCollection = $this->getLayer()->getProductCollection();
        $countBySetId      = $productCollection->getProductCountByAttributeSetId();
        $totalCount        = $productCollection->getSize();

        foreach ($this->filterList->getFilters($this->_catalogLayer) as $filter) {
            try {
                $attribute                = $filter->getAttributeModel();
                $facetCoverageRate        = $attribute->getFacetMinCoverageRate();
                $attributeCountCandidates = array_sum(array_intersect_key($countBySetId, $attribute->getAttributeSetInfo()));
                $currentCoverageRate      = $attributeCountCandidates / $totalCount * 100;

                if ($facetCoverageRate < $currentCoverageRate) {
                    $filter->addFacetToCollection();
                }
            } catch (\Exception $e) {
                $filter->addFacetToCollection();
            }
        }
    }
}
