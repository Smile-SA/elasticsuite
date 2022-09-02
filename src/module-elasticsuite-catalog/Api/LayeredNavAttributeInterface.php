<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Botis <botis@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Api;

/**
 * LayeredNavAttributeInterface class.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Botis <botis@smile.fr>
 */
interface LayeredNavAttributeInterface
{
    /**
     * Get attribute code.
     *
     * @return string
     */
    public function getAttributeCode(): string;

    /**
     * Get ES filter field.
     *
     * @return string
     */
    public function getFilterField(): string;

    /**
     * Get additional aggregation data.
     *
     * @return array
     */
    public function getAdditionalAggregationData(): array;

    /**
     * Skip attribute.
     *
     * @return bool
     */
    public function skipAttribute(): bool;
}
