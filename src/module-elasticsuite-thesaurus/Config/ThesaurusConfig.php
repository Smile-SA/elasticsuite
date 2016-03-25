<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile_ElasticSuiteThesaurus
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteThesaurus\Config;

/**
 * Thesaurus configuration.
 *
 * @category Smile_ElasticSuite
 * @package  Smile_ElasticSuiteThesaurus
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
    private $conceptsConfig;

    /**
     * Constructor.
     *
     * @param array $synonyms Synonyms configuration.
     * @param array $concepts Concepts configuration.
     */
    public function __construct($synonyms = [], $concepts = [])
    {
        $this->synonymsConfig = $synonyms;
        $this->conceptsConfig = $concepts;
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
     * @return number
     */
    public function getSynonymWeightDivider()
    {
        return isset($this->synonymsConfig['weight_divider']) ? (int) $this->synonymsConfig['weight_divider'] : 1;
    }

    /**
     * Is the concepts search enabled ?
     *
     * @return boolean
     */
    public function isConceptSearchEnabled()
    {
        return isset($this->conceptsConfig['enable']) ? (bool) $this->conceptsConfig['enable'] : false;
    }

    /**
     * Concepts search weight divider.
     *
     * @return number
     */
    public function getConceptWeightDivider()
    {
        return isset($this->conceptsConfig['weight_divider']) ? (int) $this->conceptsConfig['weight_divider'] : 1;
    }
}
