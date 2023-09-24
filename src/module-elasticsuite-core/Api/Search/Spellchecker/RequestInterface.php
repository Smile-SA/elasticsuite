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

namespace Smile\ElasticsuiteCore\Api\Search\Spellchecker;

/**
 * Spellchecking request interface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface RequestInterface
{
    /**
     * Spellcheck request index name.
     *
     * @return string
     */
    public function getIndex();

    /**
     * Spellcheck fulltext query.
     *
     * @return string
     */
    public function getQueryText();

    /**
     * Spellcheck cutoff frequency (used to detect stopwords).
     *
     * @return float
     */
    public function getCutoffFrequency();

    /**
     * Is the spellcheck request using all tokens returned by the term vectors.
     *
     * @return boolean
     */
    public function isUsingAllTokens();

    /**
     * Should the spellcheck request target the 'reference' collector field.
     *
     * @return boolean
     */
    public function isUsingReference();

    /**
     * Should the spellcheck request target the 'edge_ngram' collector field.
     *
     * @return boolean
     */
    public function isUsingEdgeNgram();
}
