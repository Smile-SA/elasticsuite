<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Layer\Filter\DataProvider;

/**
 * Custom decimal data provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Decimal extends \Magento\Catalog\Model\Layer\Filter\DataProvider\Price
{
    /**
     * Validate and parse filter request param.
     *
     * Use this one instead of the legacy to ensure negative values are properly computed.
     *
     * @param string $filter The raw filter value (XXX-XXX)
     *
     * @return array|bool
     */
    public function validateFilter($filter)
    {
        // Match a regex pattern instead of just exploding on '-' (this fail with negative numbers).
        $regexp = "/(-?[0-9]+)-(-?[0-9]+)/";
        preg_match_all($regexp, $filter, $matches);

        // Whole match + two groups matches.
        if (count($matches) !== 3) {
            return false;
        }

        $filter = [current($matches[1]), current($matches[2])];

        // Legacy check for >=0 is also removed here.
        foreach ($filter as $v) {
            if ($v === false || is_infinite((float) $v)) {
                return false;
            }
        }

        return $filter;
    }
}
