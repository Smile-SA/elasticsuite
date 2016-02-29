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

/**
 * Fuzziness Configuration object
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class FuzzinessConfiguration implements FuzzinessConfigurationInterface
{
    /**
     * @var float The fuzziness value
     */
    private $value;

    /**
     * @var int The prefix length
     */
    private $prefixLength;

    /**
     * @var int Max. expansion
     */
    private $maxExpansion;

    /**
     * RelevanceConfiguration constructor.
     *
     * @param float $value        The value
     * @param int   $prefixLength The prefix length
     * @param int   $maxExpansion The max expansion
     */
    public function __construct(
        $value,
        $prefixLength,
        $maxExpansion
    ) {
        $this->value = $value;
        $this->prefixLength = $prefixLength;
        $this->maxExpansion = $maxExpansion;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function getPrefixLength()
    {
        return $this->prefixLength;
    }

    /**
     * @return int
     */
    public function getMaxExpansion()
    {
        return $this->maxExpansion;
    }
}
