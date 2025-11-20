<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Model;

/**
 * Search usage report interface, used for KPIs and Terms reports.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
interface ReportInterface
{
    /**
     * Get report data.
     *
     * @return array
     */
    public function getData();
}
