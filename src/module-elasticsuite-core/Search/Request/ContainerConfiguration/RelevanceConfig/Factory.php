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

namespace Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration\RelevanceConfig;

use Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfigurationInterface;
use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerScopeInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfiguration\FuzzinessConfigurationInterface;

/**
 * Search relevance configuration factory.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Factory
{
    /**
     * XML root node for search relevance
     */
    const BASE_RELEVANCE_CONFIG_XML_PREFIX = 'relevance';

    /**
     * XML node for phrase match configuration
     */
    const PHRASE_MATCH_CONFIG_XML_PREFIX = 'phrase_match_configuration';

    /**
     * XML node for minimum should match configuration.
     */
    const MINIMUM_SHOULD_MATCH_CONFIG_XML_PATH = 'fulltext_base_settings/minimum_should_match';

    /**
     * XML node for tie breaker configuration.
     */
    const TIE_BREAKER_CONFIG_XML_PATH = 'fulltext_base_settings/tie_breaker';

    /**
     * XML node for cutoff frequency configuration
     */
    const CUTOFF_FREQUENCY_CONFIG_XML_PATH = 'cutoff_frequency_configuration/cutoff_frequency';

    /**
     * XML node for fuzziness configuration
     */
    const FUZZINESS_CONFIG_XML_PREFIX = 'spellchecking/fuzziness';

    /**
     * XML node for phonetic configuration
     */
    const PHONETIC_CONFIG_XML_PATH = 'spellchecking/phonetic/enable';

    /**
     * XML node for span match configuration
     */
    const SPAN_MATCH_CONFIG_XML_PREFIX = 'span_match_configuration';

    /**
     * XML node for min_score configuration
     */
    const MIN_SCORE_CONFIG_XML_PREFIX = 'min_score_configuration';

    /**
     * XML node for exact match configuration
     */
    const EXACT_MATCH_CONFIG_XML_PREFIX = 'exact_match_configuration';

    /**
     * XML node for tokens usage in term vectors configuration.
     */
    const TERM_VECTORS_TOKENS_CONFIG_XML_PATH = 'spellchecking/term_vectors/use_all_tokens';

    /**
     * XML node for reference analyzer usage in term vectors configuration.
     */
    const TERM_VECTORS_USE_REFERENCE_CONFIG_XML_PATH = 'spellchecking/term_vectors/use_reference_analyzer';

    /**
     * XML node for edge ngram analyzer(s) usage in term vectors configuration.
     */
    const TERM_VECTORS_USE_EDGE_NGRAM_CONFIG_XML_PATH = 'spellchecking/term_vectors/use_edge_ngram_analyzer';

    /**
     * @var RelevanceConfigurationInterface[]
     */
    private $cachedConfig = [];

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\RelevanceConfig\App\Config
     */
    private $scopeConfig;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var string
     */
    private $instanceName;

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager Object manager.
     * @param string                 $instanceName  Config class name.
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        $instanceName = 'Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfigurationInterface'
    ) {
        $this->scopeConfig   = $objectManager->get('Smile\ElasticsuiteCore\Search\Request\RelevanceConfig\App\Config');
        $this->objectManager = $objectManager;
        $this->instanceName  = $instanceName;
    }

    /**
     * Retrieve relevance configuration for a container.
     *
     * @param int    $storeId       Store id.
     * @param string $containerName Container identifier.
     *
     * @return \Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfigurationInterface
     */
    public function create($storeId, $containerName)
    {
        $scopeCode = $this->getScopeCode($storeId, $containerName);

        if (!isset($this->cachedConfig[$scopeCode])) {
            $instanceConfiguration          = $this->loadConfiguration($scopeCode);
            $this->cachedConfig[$scopeCode] = $this->objectManager->create($this->instanceName, $instanceConfiguration);
        }

        return $this->cachedConfig[$scopeCode];
    }

    /**
     * Load the relevance configuration by scope code.
     *
     * @param string $scopeCode Container scope code.
     *
     * @return array
     */
    protected function loadConfiguration($scopeCode)
    {
        $configurationParams = [
            'minimumShouldMatch'   => $this->getMinimumShouldMatch($scopeCode),
            'tieBreaker'           => $this->getTieBreaker($scopeCode),
            'phraseMatchBoost'     => $this->getPhraseMatchBoostConfiguration($scopeCode),
            'cutOffFrequency'      => $this->getCutoffFrequencyConfiguration($scopeCode),
            'fuzziness'            => $this->getFuzzinessConfiguration($scopeCode),
            'enablePhoneticSearch' => $this->isPhoneticSearchEnabled($scopeCode),
            'spanMatchBoost'       => $this->getSpanMatchBoostConfiguration($scopeCode),
            'spanSize'             => $this->getSpanSize($scopeCode),
            'minScore'             => $this->getMinScoreConfiguration($scopeCode),
            'useReferenceInExactMatchFilter'    => $this->isUsingReferenceInExactMatchFilter($scopeCode),
            'useAllTokens'                      => $this->isUsingAllTokensConfiguration($scopeCode),
            'useReferenceAnalyzer'              => $this->isUsingReferenceAnalyzerConfiguration($scopeCode),
            'useEdgeNgramAnalyzer'              => $this->isUsingEdgeNgramAnalyzerConfiguration($scopeCode),
            'useDefaultAnalyzerInExactMatchFilter' => $this->isUsingDefaultAnalyzerInExactMatchFilter($scopeCode),
            'exactMatchSingleTermBoostsCustomized'  => $this->areExactMatchCustomBoostValuesEnabled($scopeCode),
            'exactMatchSingleTermPhraseMatchBoost'  => $this->getExactMatchSingleTermPhraseMatchBoostConfiguration($scopeCode),
            'exactMatchSingleTermSortableBoost'     => $this->getExactMatchSortableBoostConfiguration($scopeCode),
        ];

        return $configurationParams;
    }

    /**
     * Read value into the config by path and scope.
     *
     * @param string $path      Config path.
     * @param string $scopeCode Scope coode.
     *
     * @return mixed
     */
    protected function getConfigValue($path, $scopeCode)
    {
        $scope = ContainerScopeInterface::SCOPE_STORE_CONTAINERS;

        return $this->scopeConfig->getValue($path, $scope, $scopeCode);
    }

    /**
     * Retrieve fuzziness configuration object.
     *
     * @param string $scopeCode The scope code.
     *
     * @return FuzzinessConfigurationInterface|null
     */
    private function getFuzzinessConfiguration($scopeCode)
    {
        $path = self::FUZZINESS_CONFIG_XML_PREFIX;

        $configuration = (bool) $this->getConfigValue($path . "/enable", $scopeCode);

        if ($configuration === true) {
            $configurationParams = [
                'value'        => $this->getConfigValue($path . "/value", $scopeCode),
                'prefixLength' => $this->getConfigValue($path . "/prefix_length", $scopeCode),
                'maxExpansion' => $this->getConfigValue($path . "/max_expansion", $scopeCode),
                'minimumShouldMatch' => $this->getFuzzinessMinimumShouldMatch($scopeCode),
            ];

            $configuration = $this->createFuzzinessConfiguration($configurationParams);
        }

        return $configuration === false ? null : $configuration;
    }

    /**
     * Retrieve phonetic configuration object
     *
     * @param string $scopeCode The scope code.
     *
     * @return bool
     */
    private function isPhoneticSearchEnabled($scopeCode)
    {
        return (bool) $this->getConfigValue(self::PHONETIC_CONFIG_XML_PATH, $scopeCode);
    }

    /**
     * Create a Fuzziness Configuration Object
     *
     * @param array $configurationParams Object parameters
     *
     * @return FuzzinessConfigurationInterface
     */
    private function createFuzzinessConfiguration($configurationParams)
    {
        return $this->objectManager->create(
            '\Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfiguration\FuzzinessConfigurationInterface',
            $configurationParams
        );
    }

    /**
     * Retrieve phrase boost configuration for a container.
     *
     * @param string $scopeCode The scope code
     *
     * @return bool|int
     */
    private function getPhraseMatchBoostConfiguration($scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::PHRASE_MATCH_CONFIG_XML_PREFIX;

        $boost = (bool) $this->getConfigValue($path . "/enable_phrase_match", $scopeCode);

        if ($boost === true) {
            $boost = (int) $this->getConfigValue($path . "/phrase_match_boost_value", $scopeCode);
        }

        return $boost;
    }

    /**
     * Retrieve minimum should match config for a container.
     *
     * @param string $scopeCode The scope code.
     *
     * @return string
     */
    private function getMinimumShouldMatch($scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::MINIMUM_SHOULD_MATCH_CONFIG_XML_PATH;

        return $this->getConfigValue($path, $scopeCode);
    }

    /**
     * Retrieve tie breaker config for a container.
     *
     * @param string $scopeCode The scope code.
     *
     * @return float
     */
    private function getTieBreaker($scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::TIE_BREAKER_CONFIG_XML_PATH;

        return (float) $this->getConfigValue($path, $scopeCode);
    }

    /**
     * Retrieve cutoff frequency for a container.
     *
     * @param string $scopeCode The scope code.
     *
     * @return float
     */
    private function getCutoffFrequencyConfiguration($scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::CUTOFF_FREQUENCY_CONFIG_XML_PATH;

        return (float) $this->getConfigValue($path, $scopeCode);
    }

    /**
     * Retrieve current scope code
     *
     * @param integer     $storeId       The store identifier or id.
     * @param string|null $containerName The container name.
     *
     * @return string
     */
    private function getScopeCode($storeId, $containerName)
    {
        return sprintf("%s|%s", $containerName, $storeId);
    }

    /**
     * Retrieve span boost configuration for a container.
     *
     * @param string $scopeCode The scope code
     *
     * @return bool|int
     */
    private function getSpanMatchBoostConfiguration($scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::SPAN_MATCH_CONFIG_XML_PREFIX;

        $boost = (bool) $this->getConfigValue($path . "/enable_span_match", $scopeCode);

        if ($boost === true) {
            $boost = (int) $this->getConfigValue($path . "/span_match_boost_value", $scopeCode);
        }

        return $boost;
    }

    /**
     * Retrieve span boost size configuration for a container.
     *
     * @param string $scopeCode The scope code
     *
     * @return bool|int
     */
    private function getSpanSize($scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::SPAN_MATCH_CONFIG_XML_PREFIX;

        $size = (bool) $this->getConfigValue($path . "/enable_span_match", $scopeCode);

        if ($size === true) {
            $size = (int) $this->getConfigValue($path . "/span_size", $scopeCode);
        }

        return $size;
    }

    /**
     * Retrieve min_score configuration for a container.
     *
     * @param string $scopeCode The scope code
     *
     * @return bool|int
     */
    private function getMinScoreConfiguration($scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::MIN_SCORE_CONFIG_XML_PREFIX;

        $minScore = (bool) $this->getConfigValue($path . "/enable_use_min_score", $scopeCode);

        if ($minScore === true) {
            $minScore = (int) $this->getConfigValue($path . "/min_score_value", $scopeCode);
        }

        return $minScore;
    }

    /**
     * Retrieve reference collector field usage configuration for a container.
     *
     * @param @param string $scopeCode The scope code
     *
     * @return bool
     */
    private function isUsingReferenceInExactMatchFilter($scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::EXACT_MATCH_CONFIG_XML_PREFIX;

        return (bool) $this->getConfigValue($path . "/use_reference_in_filter", $scopeCode);
    }

    /**
     * Retrieve term vectors extensive tokens usage configuration for a container.
     *
     * @param string $scopeCode The scope code
     *
     * @return bool
     */
    private function isUsingAllTokensConfiguration($scopeCode)
    {
        return (bool) $this->getConfigValue(self::TERM_VECTORS_TOKENS_CONFIG_XML_PATH, $scopeCode);
    }

    /**
     * Retrieve term vectors reference analyzer usage configuration for a container.
     *
     * @param string $scopeCode The scope code
     *
     * @return bool
     */
    private function isUsingReferenceAnalyzerConfiguration($scopeCode)
    {
        return (bool) $this->getConfigValue(self::TERM_VECTORS_USE_REFERENCE_CONFIG_XML_PATH, $scopeCode);
    }

    /**
     * Retrieve term vectors edge ngram analyzer usage configuration for a container.
     *
     * @param string $scopeCode The scope code
     *
     * @return bool
     */
    private function isUsingEdgeNgramAnalyzerConfiguration($scopeCode)
    {
        return (bool) $this->getConfigValue(self::TERM_VECTORS_USE_EDGE_NGRAM_CONFIG_XML_PATH, $scopeCode);
    }

    /**
     * Check if we should use the default analyzer of each field when building the exact match filter query.
     *
     * @param string $scopeCode The scope code
     *
     * @return bool
     */
    private function isUsingDefaultAnalyzerInExactMatchFilter($scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::EXACT_MATCH_CONFIG_XML_PREFIX;

        return (bool) $this->getConfigValue($path . "/use_default_analyzer", $scopeCode);
    }

    /**
     * Check if custom boost values for exact match in whitespace and sortable version of fields
     * should be applied.
     *
     * @param string $scopeCode The scope code
     *
     * @return bool
     */
    private function areExactMatchCustomBoostValuesEnabled($scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::EXACT_MATCH_CONFIG_XML_PREFIX;

        return (bool) $this->getConfigValue($path . "/enable_single_term_custom_boost_values", $scopeCode);
    }

    /**
     * Return the configured custom boost value for whitespace fields in exact match queries.
     *
     * @param string $scopeCode The scope code
     *
     * @return int
     */
    private function getExactMatchSingleTermPhraseMatchBoostConfiguration($scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::EXACT_MATCH_CONFIG_XML_PREFIX;

        return (int) $this->getConfigValue($path . "/single_term_phrase_match_boost_value", $scopeCode);
    }

    /**
     * Return the configured custom boost value for sortable fields in exact match queries.
     *
     * @param string $scopeCode The scope code
     *
     * @return int
     */
    private function getExactMatchSortableBoostConfiguration($scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::EXACT_MATCH_CONFIG_XML_PREFIX;

        return (int) $this->getConfigValue($path . "/sortable_boost_value", $scopeCode);
    }

    /**
     * Return the minimum should match configured for fuzzy search.
     * Either the default one, or a particular if configured so.
     *
     * @param string $scopeCode The scope code
     *
     * @return string
     */
    private function getFuzzinessMinimumShouldMatch($scopeCode)
    {
        $minimumShouldMatch = $this->getMinimumShouldMatch($scopeCode);
        $useDefaultMsmPath  = self::FUZZINESS_CONFIG_XML_PREFIX . '/use_default_minimum_should_match';
        $useDefaultMsm      = (bool) $this->getConfigValue($useDefaultMsmPath, $scopeCode);
        $fuzzyMsmPath       = self::FUZZINESS_CONFIG_XML_PREFIX . '/minimum_should_match';
        $fuzzyMsm           = (string) $this->getConfigValue($fuzzyMsmPath, $scopeCode);

        if ($useDefaultMsm === false && $fuzzyMsm !== '') {
            $minimumShouldMatch = $fuzzyMsm;
        }

        return $minimumShouldMatch;
    }
}
