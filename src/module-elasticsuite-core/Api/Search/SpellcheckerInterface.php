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

namespace Smile\ElasticsuiteCore\Api\Search;

use Smile\ElasticsuiteCore\Api\Search\Spellchecker\RequestInterface;

/**
 * Spellchecker component interface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface SpellcheckerInterface
{
    const SPELLING_TYPE_EXACT          = 1;
    const SPELLING_TYPE_MOST_EXACT     = 2;
    const SPELLING_TYPE_MOST_FUZZY     = 3;
    const SPELLING_TYPE_FUZZY          = 4;
    const SPELLING_TYPE_PURE_STOPWORDS = 5;

    /**
     * Returns the type of spelling of a fultext query :
     *
     * - SPELLING_TYPE_EXACT          : All terms of the text query exist and are exactly spelled.
     * - SPELLING_TYPE_MOST_EXACT     : All of the text query terms exist. Some are types using an analyzed form.
     * - SPELLING_TYPE_MOST_FUZZY     : At least one term of the text query was exists in the index.
     * - SPELLING_TYPE_FUZZY          : All terms of the text query was mispelled.
     * - SPELLING_TYPE_PURE_STOPWORDS : All terms of the text query exist and are stopwords (a, the, ...).
     *
     * @param RequestInterface $request Spellchecking query.
     *
     * @return integer
     */
    public function getSpellingType(RequestInterface $request);
}
