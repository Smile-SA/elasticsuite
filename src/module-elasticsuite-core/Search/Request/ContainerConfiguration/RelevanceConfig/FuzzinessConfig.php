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
namespace Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration\RelevanceConfig;

use Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfiguration\FuzzinessConfigurationInterface;

/**
 * Fuzziness Configuration object
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class FuzzinessConfig implements FuzzinessConfigurationInterface
{
    /**
     * @var float The fuzziness value
     */
    private $value;

    /**
     * @var integer The prefix length
     */
    private $prefixLength;

    /**
     * @var integer Max. expansion
     */
    private $maxExpansion;

    /**
     * @var string
     */
    private $minimumShouldMatch;

    /**
     * RelevanceConfiguration constructor.
     *
     * @param float  $value              The value
     * @param int    $prefixLength       The prefix length
     * @param int    $maxExpansion       The max expansion
     * @param string $minimumShouldMatch Minimum should match clause of the fuzzy query.
     */
    public function __construct(
        $value,
        $prefixLength,
        $maxExpansion,
        $minimumShouldMatch
    ) {
        $this->value = $value;
        $this->prefixLength = $prefixLength;
        $this->maxExpansion = $maxExpansion;
        $this->minimumShouldMatch = $minimumShouldMatch;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrefixLength()
    {
        return (int) $this->prefixLength;
    }

    /**
     * {@inheritDoc}
     */
    public function getMaxExpansion()
    {
        return (int) $this->maxExpansion;
    }

    /**
     * {@inheritDoc}
     */
    public function getMinimumShouldMatch()
    {
        return (string) $this->minimumShouldMatch;
    }
}
