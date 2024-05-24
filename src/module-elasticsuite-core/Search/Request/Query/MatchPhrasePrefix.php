<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Query;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * match_phrase_prefix query definition implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class MatchPhrasePrefix implements QueryInterface
{
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
     * @var integer
     */
    private $maxExpansions;

    /**
     * @param string  $queryText     Matched text.
     * @param string  $field         Query field.
     * @param int     $maxExpansions Max expansions.
     * @param string  $name          Query name.
     * @param integer $boost         Query boost.
     */
    public function __construct(
        $queryText,
        $field,
        $maxExpansions = 10,
        $name = null,
        $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->name          = $name;
        $this->queryText     = $queryText;
        $this->field         = $field;
        $this->boost         = $boost;
        $this->maxExpansions = $maxExpansions;
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
        return QueryInterface::TYPE_MATCHPHRASEPREFIX;
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
     * @return int
     */
    public function getMaxExpansions()
    {
        return $this->maxExpansions;
    }
}
