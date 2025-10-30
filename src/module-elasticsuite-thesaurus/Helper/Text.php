<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteThesaurus\Helper;

/**
 * Text manipulation helper.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Pierre Gauthier <pigau@smile.fr>
 */
class Text
{
    /**
     * Partial implementation of a multi-byte aware version of substr_replace.
     * Required because the tokens offsets used as for parameters start and length
     * are expressed as a number of (UTF-8) characters, independently of the number of bytes.
     * Does not accept arrays as first and second parameters.
     * Source: https://github.com/fluxbb/utf8/blob/master/functions/substr_replace.php
     * Alternative: https://gist.github.com/bantya/563d7d070c286ba1b5a83b9036f0561a
     *
     * @param string $string      Input string
     * @param string $replacement Replacement string
     * @param mixed  $start       Start offset
     * @param mixed  $length      Length of replacement
     *
     * @return mixed
     */
    public function mbSubstrReplace($string, $replacement, $start, $length = null)
    {
        preg_match_all('/./us', $string, $stringChars);
        preg_match_all('/./us', $replacement, $replacementChars);
        $length = is_int($length) ? $length : mb_strlen($string);
        array_splice($stringChars[0], $start, $length, $replacementChars[0]);

        return implode($stringChars[0]);
    }

    /**
     * Count the number of words in a given text.
     *
     * @param string $text The input text.
     *
     * @return int
     */
    public function mbWordCount(string $text): int
    {
        preg_match_all('/[\p{L}\p{N}\']+/u', $text, $matches);

        return count($matches[0]);
    }
}
