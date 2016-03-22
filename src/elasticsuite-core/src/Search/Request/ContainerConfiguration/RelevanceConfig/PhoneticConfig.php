<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Search\Request\ContainerConfiguration\RelevanceConfig;

use Smile\ElasticSuiteCore\Api\Search\Request\Container\RelevanceConfiguration\FuzzinessConfigurationInterface;
use Smile\ElasticSuiteCore\Api\Search\Request\Container\RelevanceConfiguration\PhoneticConfigurationInterface;

/**
 * Phonetic Configuration Object
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class PhoneticConfig implements PhoneticConfigurationInterface
{
    /**
     * @var FuzzinessConfigurationInterface
     */
    private $fuzzinessConfiguration;

    /**
     * PhoneticConfiguration constructor.
     *
     * @param FuzzinessConfigurationInterface $fuzziness The fuzziness configuration
     */
    public function __construct(FuzzinessConfigurationInterface $fuzziness = null)
    {
        $this->fuzzinessConfiguration = $fuzziness;
    }

    /**
     * Check if fuzziness is enabled
     *
     * @return bool
     */
    public function isFuzzinessEnabled()
    {
        return ($this->fuzzinessConfiguration !== null);
    }

    /**
     * Retrieve Fuzziness Configuration
     *
     * @return FuzzinessConfigurationInterface
     */
    public function getFuzzinessConfiguration()
    {
        return $this->fuzzinessConfiguration;
    }
}
