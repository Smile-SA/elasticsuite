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
     * Spellcheck request document type.
     *
     * @return string
     */
    public function getType();

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
}
