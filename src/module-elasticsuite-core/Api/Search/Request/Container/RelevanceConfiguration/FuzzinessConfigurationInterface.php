<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfiguration;

/**
 * FuzzinessConfiguration object interface
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface FuzzinessConfigurationInterface
{
    /**
     * Get Fuzziness value
     *
     * @return string|integer
     */
    public function getValue();

    /**
     * Get Prefix Length
     *
     * @return int
     */
    public function getPrefixLength();

    /**
     * Get Max. Expansion
     *
     * @return int
     */
    public function getMaxExpansion();

    /**
     * @return string
     */
    public function getMinimumShouldMatch();
}
