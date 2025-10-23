<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage;

/**
 * Interface ChartInterface
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
interface ChartInterface
{
    /**
     * Constont for red background/drawing chart color
     */
    const COLOR_RED     = '#FE7F53';

    /**
     * Constont for blue background/drawing chart color
     */
    const COLOR_BLUE    = '#367AFF';

    /**
     * Constont for green background/drawing chart color
     */
    const COLOR_GREEN   = '#25BC94';

    /**
     * Constant for yellow background/drawing chart color
     */
    const COLOR_YELLOW  = '#FFB800';

    /**
     * Constant for gray background/drawing chart color
     */
    const COLOR_GRAY    = '#6B7280';

    /**
     * Constant for pink background/drawing chart color
     */
    const COLOR_PINK    = '#EC4899';

    /**
     * Return chart data in the format expected by Google Charts API as a JSON encoded string.
     * (see https://developers.google.com/chart/interactive/docs/reference#dataparam)
     *
     * @return array
     */
    public function getChartData();

    /**
     * Return chart options as a JSON encoded string.
     *
     * @return string
     */
    public function getChartOptions();
}
