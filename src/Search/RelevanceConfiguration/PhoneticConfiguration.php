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
namespace Smile\ElasticSuiteCore\Search\RelevanceConfiguration;

use Smile\ElasticSuiteCore\Api\SearchRelevanceConfiguration\FuzzinessConfigurationInterface;
use Smile\ElasticSuiteCore\Api\SearchRelevanceConfiguration\PhoneticConfigurationInterface;

/**
 * Phonetic Configuration Object
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class PhoneticConfiguration implements PhoneticConfigurationInterface
{
    /**
     * @var \Smile\ElasticSuiteCore\Api\SearchRelevanceConfiguration\FuzzinessConfigurationInterface
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
}
