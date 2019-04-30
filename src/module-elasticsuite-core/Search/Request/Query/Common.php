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

namespace Smile\ElasticsuiteCore\Search\Request\Query;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * ES common query definition implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Common extends Match
{
    /**
     * @var string
     */
    const DEFAULT_CUTOFF_FREQUENCY = 0.1;

    /**
     * @var float
     */
    private $cutoffFrequency;

    /**
     * Constructor.
     *
     * @param string  $queryText          Matched text.
     * @param string  $field              Query field.
     * @param float   $cutoffFrequency    Cutoff frequency.
     * @param string  $minimumShouldMatch Minimum should match for the match query.
     * @param string  $name               Query name.
     * @param integer $boost              Query boost.
     */
    public function __construct(
        $queryText,
        $field,
        $cutoffFrequency = self::DEFAULT_CUTOFF_FREQUENCY,
        $minimumShouldMatch = self::DEFAULT_MINIMUM_SHOULD_MATCH,
        $name = null,
        $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        parent::__construct($queryText, $field, $minimumShouldMatch, $name, $boost);
        $this->cutoffFrequency    = $cutoffFrequency;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return QueryInterface::TYPE_COMMON;
    }

    /**
     * Query cutoff frequency.
     *
     * @return float
     */
    public function getCutoffFrequency()
    {
        return $this->cutoffFrequency;
    }
}
