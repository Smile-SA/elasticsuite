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
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource\PriceData;

/**
 * Price data reader to be implemented for each product type.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface PriceDataReaderInterface
{
    /**
     * Read the product price.
     *
     * @param array $priceData Raw price data.
     *
     * @return float
     */
    public function getPrice($priceData);

    /**
     * Read the product original price.
     *
     * @param array $priceData Raw price data.
     *
     * @return float
     */
    public function getOriginalPrice($priceData);
}
