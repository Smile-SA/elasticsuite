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
namespace Smile\ElasticSuiteCore\Search;

use Smile\ElasticSuiteCore\Api\SearchRelevanceConfiguration\FuzzinessConfigurationInterface;
use Smile\ElasticSuiteCore\Api\SearchRelevanceConfiguration\PhoneticConfigurationInterface;
use Smile\ElasticSuiteCore\Api\SearchRelevanceConfigurationInterface;

/**
 * Relevance Configuration object
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class RelevanceConfiguration implements SearchRelevanceConfigurationInterface
{
    /**
     * @var int|null
     */
    private $phraseMatchBoost;

    /**
     * @var float
     */
    private $cutOffFrequency;

    /**
     * @var null|\Smile\ElasticSuiteCore\Api\SearchRelevanceConfiguration\FuzzinessConfigurationInterface
     */
    private $fuzzinessConfiguration;

    /**
     * @var null|\Smile\ElasticSuiteCore\Api\SearchRelevanceConfiguration\PhoneticConfigurationInterface
     */
    private $phoneticConfiguration;

    /**
     * RelevanceConfiguration constructor.
     *
     * @param int|null                             $phraseMatchBoost The Phrase match boost value, or null if not
     *                                                               enabled
     * @param float                                $cutOffFrequency  The cutoff Frequency value
     * @param FuzzinessConfigurationInterface|null $fuzziness        The fuzziness Configuration, or null
     * @param PhoneticConfigurationInterface|null  $phonetic         The phonetic Configuration, or null
     *
     * @internal param $fuzziness
     * @internal param $phonetic
     */
    public function __construct(
        $phraseMatchBoost,
        $cutOffFrequency,
        FuzzinessConfigurationInterface $fuzziness = null,
        PhoneticConfigurationInterface $phonetic = null
    ) {
        $this->phraseMatchBoost = $phraseMatchBoost;
        $this->cutOffFrequency = $cutOffFrequency;
        $this->fuzzinessConfiguration = $fuzziness;
        $this->phoneticConfiguration = $phonetic;
    }

    /**
     * @return int|null
     */
    public function getPhraseMatchBoost()
    {
        return $this->phraseMatchBoost;
    }

    /**
     * @return int
     */
    public function getCutOffFrequency()
    {
        return $this->cutOffFrequency;
    }

    /**
     * Retrieve FuzzinessConfiguration
     *
     * @return FuzzinessConfigurationInterface|null
     */
    public function getFuzzinessConfiguration()
    {
        return $this->fuzzinessConfiguration;
    }

    /**
     * Retrieve Phonetic Configuration
     *
     * @return PhoneticConfigurationInterface|null
     */
    public function getPhoneticConfiguration()
    {
        return $this->phoneticConfiguration;
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
     * Check if phonetic search is enabled
     *
     * @return bool
     */
    public function isPhoneticSearchEnabled()
    {
        return ($this->phoneticConfiguration !== null);
    }
}
