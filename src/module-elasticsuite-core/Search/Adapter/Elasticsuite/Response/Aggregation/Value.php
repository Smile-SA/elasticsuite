<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\Aggregation;

/**
 * ElasticSuite aggregations bucket value.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Value extends \Magento\Framework\Search\Response\Aggregation\Value
{
    /**
     * @var \Magento\Framework\Search\Response\Aggregation
     */
    private $aggregations;

    /**
     * Constructor.
     *
     * @param string|array                                   $value        Current bucket value.
     * @param array                                          $metrics      Metrics.
     * @param \Magento\Framework\Search\Response\Aggregation $aggregations Sub-aggregations.
     */
    public function __construct($value, $metrics, $aggregations)
    {
        parent::__construct($value, $metrics);

        $this->aggregations = $aggregations;
    }

    /**
     * Return bucket sub aggregations.
     *
     * @return \Magento\Framework\Search\Response\Aggregation
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }
}
