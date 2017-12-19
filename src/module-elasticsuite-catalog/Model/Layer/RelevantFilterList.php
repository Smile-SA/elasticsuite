<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Layer;

/**
 * Return relevant filter for a layer.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class RelevantFilterList
{
    /**
     * Get relevant filters for the current layer.
     *
     * @param \Magento\Catalog\Model\Layer                          $layer   Layer.
     * @param \Magento\Catalog\Model\Layer\Filter\FilterInterface[] $filters List of available filters.
     *
     * @return \Magento\Catalog\Model\Layer\Filter\FilterInterface[]
     */
    public function getRelevantFilters(\Magento\Catalog\Model\Layer $layer, array $filters)
    {
        $productCollection = $layer->getProductCollection();
        $coverageRates     = $this->getCoverageRates($productCollection);

        foreach ($filters as $filterName => $filter) {
            try {
                $attribute       = $filter->getAttributeModel();
                $attributeCode   = $attribute->getAttributeCode();
                $minCoverageRate = $attribute->getFacetMinCoverageRate();

                $isRelevant = isset($coverageRates[$attributeCode]) && ($coverageRates[$attributeCode] >= $minCoverageRate);

                if ($isRelevant === false) {
                    unset($filters[$filterName]);
                }
            } catch (\Exception $e) {
                ;
            }
        }

        return $filters;
    }

    /**
     * Retrieve Attributes coverage rate for a given product collection.
     *
     * @param \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection $collection Product Collection
     *
     * @return array
     */
    private function getCoverageRates(\Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection $collection)
    {
        $totalCount    = $collection->getSize();
        $coverageRates = [];

        foreach ($collection->getProductCountByAttributeCode() as $attributeCode => $productCount) {
            $coverageRates[$attributeCode] = $productCount / $totalCount * 100;
        }

        return $coverageRates;
    }
}
