<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteThesaurus\Config;

/**
 * Thesaurus configuration.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ThesaurusConfig
{
    /**
     * @var array
     */
    private $synonymsConfig;

    /**
     * @var array
     */
    private $expansionsConfig;

    /**
     * @var array
     */
    private $general;

    /**
     * Constructor.
     *
     * @param array $general    General configuration.
     * @param array $synonyms   Synonyms configuration.
     * @param array $expansions Expansions configuration.
     */
    public function __construct($general = [], $synonyms = [], $expansions = [])
    {
        $this->general          = $general;
        $this->synonymsConfig   = $synonyms;
        $this->expansionsConfig = $expansions;
    }

    /**
     * Max allowed rewrites for the synonym engine.
     *
     * @return int
     */
    public function getMaxRewrites()
    {
        return (int) $this->general['max_rewrites'];
    }

    /**
     * Is the synonyms search enabled ?
     *
     * @return boolean
     */
    public function isSynonymSearchEnabled()
    {
        return isset($this->synonymsConfig['enable']) ? (bool) $this->synonymsConfig['enable'] : false;
    }

    /**
     * Synonyms search weight divider.
     *
     * @return int
     */
    public function getSynonymWeightDivider()
    {
        return isset($this->synonymsConfig['weight_divider']) ? (int) $this->synonymsConfig['weight_divider'] : -1;
    }

    /**
     * Is the concepts search enabled ?
     *
     * @return boolean
     */
    public function isExpansionSearchEnabled()
    {
        return isset($this->expansionsConfig['enable']) ? (bool) $this->expansionsConfig['enable'] : false;
    }

    /**
     * Concepts search weight divider.
     *
     * @return int
     */
    public function getExpansionWeightDivider()
    {
        return isset($this->expansionsConfig['weight_divider']) ? (int) $this->expansionsConfig['weight_divider'] : -1;
    }
}
