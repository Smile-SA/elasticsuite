<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogGraphQl
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogGraphQl\Model\Layer\Filter\ViewMore;

/**
 * ViewMore context. Used as a singleton to pass filter name to the aggregation modifier.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Context
{
    /**
     * @var null|string
     */
    private $filterName = null;

    /**
     * @param string $filterName The filter name
     */
    public function setFilterName(string $filterName)
    {
        $this->filterName = $filterName;
    }

    /**
     * Get filter name
     *
     * @return string|null
     */
    public function getFilterName()
    {
        return $this->filterName;
    }
}
