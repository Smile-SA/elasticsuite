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
 * More like this search request query implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class MoreLikeThis implements QueryInterface
{
    /**
     * @var string
     */
    const DEFAULT_MINIMUM_SHOULD_MATCH = "1";

    /**
     * @var integer
     */
    const DEFAULT_BOOST_TERMS = 1;

    /**
     * @var integer
     */
    const DEFAULT_MIN_TERM_FREQ = 1;

    /**
     * @var integer
     */
    const DEFAULT_MAX_QUERY_TERMS = 25;

    /**
     * @var integer
     */
    const DEFAULT_MIN_DOC_FREQ = 2;

    /**
     * @var integer
     */
    const DEFAULT_MAX_DOC_FREQ = 2147483647;

    /**
     * @var integer
     */
    const DEFAULT_MIN_WORD_LENGTH = 0;

    /**
     * @var integer
     */
    const DEFAULT_MAX_WORD_LENGTH = 0;

    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $boost;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var array|string
     */
    private $like;

    /**
     * @var string
     */
    private $minimumShouldMatch;

    /**
     * @var integer
     */
    private $boostTerms;

    /**
     * @var integer
     */
    private $minTermFreq;

    /**
     * @var integer
     */
    private $minDocFreq;

    /**
     * @var integer
     */
    private $maxDocFreq;

    /**
     * @var integer
     */
    private $minWordLength;

    /**
     * @var integer
     */
    private $maxWordLength;

    /**
     * @var integer
     */
    private $maxQueryTerms;

    /**
     * @var boolean
     */
    private $includeOriginalDocs;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param array        $fields              Used fields.
     * @param array|string $like                MLT like clause (doc ids or query string).
     * @param integer      $minimumShouldMatch  Minimum should match in query generated.
     * @param integer      $boostTerms          TF-IDF term boosting value.
     * @param integer      $minTermFreq         Minimum term freq for a term to be considered.
     * @param integer      $minDocFreq          Minimum doc freq for a term to be considered.
     * @param integer      $maxDocFreq          Maximum doc freq for a term to be considered.
     * @param integer      $maxQueryTerms       Maximum number of term in generated queries.
     * @param integer      $minWordLength       Minimum length of word to consider.
     * @param integer      $maxWordLength       Maximum length of word to consider.
     * @param integer      $includeOriginalDocs Include original doc in the result set.
     * @param string       $name                Query name.
     * @param integer      $boost               Query boost.
     */
    public function __construct(
        array $fields,
        $like,
        $minimumShouldMatch = self::DEFAULT_MINIMUM_SHOULD_MATCH,
        $boostTerms = self::DEFAULT_BOOST_TERMS,
        $minTermFreq = self::DEFAULT_MIN_TERM_FREQ,
        $minDocFreq = self::DEFAULT_MIN_DOC_FREQ,
        $maxDocFreq = self::DEFAULT_MAX_DOC_FREQ,
        $maxQueryTerms = self::DEFAULT_MAX_QUERY_TERMS,
        $minWordLength = self::DEFAULT_MIN_WORD_LENGTH,
        $maxWordLength = self::DEFAULT_MAX_WORD_LENGTH,
        $includeOriginalDocs = false,
        $name = null,
        $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->fields              = $fields;
        $this->like                = $like;
        $this->minimumShouldMatch  = $minimumShouldMatch;
        $this->boostTerms          = $boostTerms;
        $this->minTermFreq         = $minTermFreq;
        $this->minDocFreq          = $minDocFreq;
        $this->maxDocFreq          = $maxDocFreq;
        $this->maxQueryTerms       = $maxQueryTerms;
        $this->name                = $name;
        $this->boost               = $boost;
        $this->includeOriginalDocs = $includeOriginalDocs;
        $this->minWordLength       = $minWordLength;
        $this->maxWordLength       = $maxWordLength;
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
        return QueryInterface::TYPE_MORELIKETHIS;
    }

    /**
     * Fields used by the MLT query.
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * List of similar doc / queries.
     *
     * @return string|array
     */
    public function getLike()
    {
        return $this->like;
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

    /**
     * Value of the TF-IDF term boost.
     *
     * @return integer
     */
    public function getBoostTerms()
    {
        return $this->boostTerms;
    }

    /**
     * Minimum term freq for a term to be considered.
     *
     * @return integer
     */
    public function getMinTermFreq()
    {
        return (int) $this->minTermFreq;
    }

    /**
     * Minimum doc freq for a term to be considered.
     *
     * @return integer
     */
    public function getMinDocFreq()
    {
        return (int) $this->minDocFreq;
    }

    /**
     * Maximum doc freq for a term to be considered.
     *
     * @return integer
     */
    public function getMaxDocFreq()
    {
        return (int) $this->maxDocFreq;
    }

    /**
     * Maximum number of term per generated query.
     *
     * @return integer
     */
    public function getMaxQueryTerms()
    {
        return (int) $this->maxQueryTerms;
    }

    /**
     * Indicates if orginial docs should be included in the result.
     *
     * @return boolean
     */
    public function includeOriginalDocs()
    {
        return $this->includeOriginalDocs;
    }

    /**
     * Minimum doc freq for a term to be considered.
     *
     * @return integer
     */
    public function getMinWordLength()
    {
        return (int) $this->minWordLength;
    }

    /**
     * Maximum doc freq for a term to be considered.
     *
     * @return integer
     */
    public function getMaxWordLength()
    {
        return (int) $this->maxWordLength;
    }
}
