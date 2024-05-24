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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Query;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Match query definition implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class MatchQuery implements QueryInterface
{
    /**
     * @var string
     */
    const DEFAULT_MINIMUM_SHOULD_MATCH = "1";

    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $boost;

    /**
     * @var string
     */
    private $queryText;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $minimumShouldMatch;

    /**
     *
     * @param string  $queryText          Matched text.
     * @param string  $field              Query field.
     * @param string  $minimumShouldMatch Minimum should match for the match query.
     * @param string  $name               Query name.
     * @param integer $boost              Query boost.
     */
    public function __construct(
        $queryText,
        $field,
        $minimumShouldMatch = self::DEFAULT_MINIMUM_SHOULD_MATCH,
        $name = null,
        $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->name               = $name;
        $this->queryText          = $queryText;
        $this->field              = $field;
        $this->minimumShouldMatch = $minimumShouldMatch;
        $this->boost              = $boost;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getBoost()
    {
        return $this->boost;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return QueryInterface::TYPE_MATCH;
    }

    /**
     * Query match text.
     *
     * @return string
     */
    public function getQueryText()
    {
        return $this->queryText;
    }

    /**
     * Query field.
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Minimum should match for the match query.
     *
     * @return string
     */
    public function getMinimumShouldMatch()
    {
        return $this->minimumShouldMatch;
    }
}
