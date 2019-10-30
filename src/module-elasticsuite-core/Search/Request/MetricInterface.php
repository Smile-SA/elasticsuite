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

namespace Smile\ElasticsuiteCore\Search\Request;

/**
 * Interface for metrics.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface MetricInterface
{
    /**
     * Available metric types.
     */
    const TYPE_AVG            = 'avg';
    const TYPE_MIN            = 'min';
    const TYPE_MAX            = 'max';
    const TYPE_SUM            = 'sum';
    const TYPE_STATS          = 'stats';
    const TYPE_EXTENDED_STATS = 'extended_stats';
    const TYPE_CARDINALITY    = 'cardinality';
    const TYPE_PERCENTILES    = 'percentiles';

    /**
     * Metric type.
     *
     * @return string
     */
    public function getType();

    /**
     * Metric field.
     *
     * @return string
     */
    public function getField();

    /**
     * Metric name.
     *
     * @return string
     */
    public function getName();

    /**
     * Metric extra config.
     *
     * @return array
     */
    public function getConfig();
}
