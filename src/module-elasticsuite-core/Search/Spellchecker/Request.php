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

namespace Smile\ElasticsuiteCore\Search\Spellchecker;

use Smile\ElasticsuiteCore\Api\Search\Spellchecker\RequestInterface;

/**
 * DISCLAIMER
 *
 * Spellchecker request implementation
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Request implements RequestInterface
{
    /**
     * @var string
     */
    private $index;

    /**
     * @var string
     */
    private $queryText;

    /**
     * @var float
     */
    private $cutoffFrequency;

    /**
     * @var boolean
     */
    private $isUsingAllTokens;

    /**
     * @var boolean
     */
    private $isUsingReference;

    /**
     * @var boolean
     */
    private $isUsingEdgeNgram;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param string  $index            Spellcheck request index name.
     * @param string  $queryText        Spellcheck fulltext query.
     * @param float   $cutoffFrequency  Spellcheck cutoff frequency (used to detect stopwords).
     * @param boolean $isUsingAllTokens Is spellcheck using all tokens returned by term vectors.
     * @param boolean $isUsingReference Should the reference analyzer be included in the spellcheck request.
     * @param boolean $isUsingEdgeNgram Should the edge ngram based analyzers be included in the spellcheck request.
     */
    public function __construct(
        $index,
        $queryText,
        $cutoffFrequency,
        $isUsingAllTokens,
        $isUsingReference,
        $isUsingEdgeNgram
    ) {
        $this->index           = $index;
        $this->queryText       = $queryText;
        $this->cutoffFrequency = $cutoffFrequency;
        $this->isUsingAllTokens = $isUsingAllTokens;
        $this->isUsingReference = $isUsingReference;
        $this->isUsingEdgeNgram = $isUsingEdgeNgram;
    }

    /**
     * {@inheritDoc}
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryText()
    {
        return $this->queryText;
    }

    /**
     * {@inheritDoc}
     */
    public function getCutoffFrequency()
    {
        return $this->cutoffFrequency;
    }

    /**
     * {@inheritDoc}
     */
    public function isUsingAllTokens()
    {
        return $this->isUsingAllTokens;
    }

    /**
     * {@inheritDoc}
     */
    public function isUsingReference()
    {
        return $this->isUsingReference;
    }

    /**
     * {@inheritDoc}
     */
    public function isUsingEdgeNgram()
    {
        return $this->isUsingEdgeNgram;
    }
}
