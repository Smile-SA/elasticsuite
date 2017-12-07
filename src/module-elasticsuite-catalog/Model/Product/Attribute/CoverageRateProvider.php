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
namespace Smile\ElasticsuiteCatalog\Model\Product\Attribute;

/**
 * Coverage Rate provider for product collection.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CoverageRateProvider
{
    /**
     * Retrieve Attributes coverage rate for a given product collection.
     *
     * @param \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection $collection Product Collection
     *
     * @return array
     */
    public function getCoverageRates(\Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection $collection)
    {
        $totalCount    = $collection->getSize();
        $coverageRates = [];

        foreach ($collection->getProductCountByAttributeCode() as $attributeCode => $attributeCount) {
            $coverageRates[$attributeCode] = (int) $attributeCount / $totalCount * 100;
        }

        return $coverageRates;
    }
}
