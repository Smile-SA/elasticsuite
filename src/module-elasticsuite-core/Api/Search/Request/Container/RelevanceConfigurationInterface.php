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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Api\Search\Request\Container;

/**
 * Search Relevance configuration interface.
 * Used to retrieve relevance configuration
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface RelevanceConfigurationInterface
{
    /**
     * @return string
     */
    public function getMinimumShouldMatch();

    /**
     * @return float
     */
    public function getTieBreaker();

    /**
     * @return int|false
     */
    public function getPhraseMatchBoost();

    /**
     * Retrieve Cutoff Frequency
     *
     * @return float
     */
    public function getCutOffFrequency();

    /**
     * Check if fuzziness is enabled
     *
     * @return bool
     */
    public function isFuzzinessEnabled();

    /**
     * Check if phonetic search is enabled
     *
     * @return bool
     */
    public function isPhoneticSearchEnabled();

    /**
     * Retrieve FuzzinessConfiguration
     *
     * @return \Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfiguration\FuzzinessConfigurationInterface|null
     */
    public function getFuzzinessConfiguration();
}
