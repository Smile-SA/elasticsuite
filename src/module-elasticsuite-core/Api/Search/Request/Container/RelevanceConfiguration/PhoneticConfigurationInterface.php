<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfiguration;

/**
 * Phonetic Search Configuration Interface
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface PhoneticConfigurationInterface
{
    /**
     * Check if fuzziness is enabled
     *
     * @return bool
     */
    public function isFuzzinessEnabled();

    /**
     * Return fuzziness configuration.
     *
     * @return FuzzinessConfigurationInterface
     */
    public function getFuzzinessConfiguration();
}
