<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource\PriceData;

/**
 * Price data parser used for grouped and bundled products.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class PriceGrouped implements PriceDataReaderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getPrice($priceData)
    {
        return $priceData['min_price'];
    }

    /**
     * {@inheritDoc}
     */
    public function getOriginalPrice($priceData)
    {
        return $priceData['min_price'];
    }
}
