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

namespace Smile\ElasticsuiteCore\Index\Analysis\Config;

use Magento\Framework\Json\Decoder;

/**
 * Convert analysis configuration XML file.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Converter implements \Magento\Framework\Config\ConverterInterface
{
    const ROOT_NODE_NAME             = 'analysis';
    const CHAR_FILTER_TYPE_ROOT_NODE = 'char_filters';
    const CHAR_FILTER_TYPE_NODE      = 'char_filter';
    const FILTER_TYPE_ROOT_NODE      = 'filters';
    const FILTER_TYPE_NODE           = 'filter';
    const TOKENIZER_TYPE_ROOT_NODE   = 'tokenizers';
    const TOKENIZER_TYPE_NODE        = 'tokenizer';
    const ANALYZER_TYPE_ROOT_NODE    = 'analyzers';
    const ANALYZER_TYPE_NODE         = 'analyzer';
    const NORMALIZER_TYPE_ROOT_NODE  = 'normalizers';
    const NORMALIZER_TYPE_NODE       = 'normalizer';
    const STEMMER_TYPE_ROOT_NODE     = 'stemmers';
    const STEMMER_GROUP_TYPE_NODE    = 'group';
    const STEMMER_TYPE_NODE          = 'stemmer';
    const LANGUAGE_DEFAULT           = 'default';

    /**
     * @var Decoder
     */
    private $jsonDecoder;

    /**
     * Constructor.
     *
     * @param Decoder $jsonDecoder JSON Decoder.
     */
    public function __construct(Decoder $jsonDecoder)
    {
        $this->jsonDecoder = $jsonDecoder;
    }

    /**
     * Convert dom node tree to array.
     *
     * @param mixed $source Configuration XML source.
     *
     * @return array
     */
    public function convert($source)
    {
        $xpath = new \DOMXPath($source);

        $defaultConfig = $this->getDefaultConfiguration($xpath);
        $configuration = ['default' => $defaultConfig];

        foreach ($this->getAllLanguages($xpath) as $language) {
            $configuration[$language] = $this->getLanguageConfiguration($xpath, $language, $defaultConfig);
        }

        $configuration['default'][self::STEMMER_TYPE_ROOT_NODE] = $this->getAllStemmersOptions(
            $xpath,
            $this->getAllDefaultLanguageStemmers($xpath)
        );

        return $configuration;
    }

    /**
     * Return default configuration available for all languages.
     *
     * @param \DOMXPath $xpath XPath access to the document parsed.
     *
     * @return array
     */
    private function getDefaultConfiguration(\DOMXPath $xpath)
    {
        $charFilters = $this->parseFilters($xpath, self::CHAR_FILTER_TYPE_ROOT_NODE, self::CHAR_FILTER_TYPE_NODE);
        $filters     = $this->parseFilters($xpath, self::FILTER_TYPE_ROOT_NODE, self::FILTER_TYPE_NODE);
        $tokenizers  = $this->parseFilters($xpath, self::TOKENIZER_TYPE_ROOT_NODE, self::TOKENIZER_TYPE_NODE);
        $charFilterKeys = array_keys($charFilters);
        $filterKeys = array_keys($filters);
        $analyzers   = $this->parseAnalyzers($xpath, $charFilterKeys, $filterKeys);
        $normalizers = $this->parseAnalyzers(
            $xpath,
            $charFilterKeys,
            $filterKeys,
            self::LANGUAGE_DEFAULT,
            self::NORMALIZER_TYPE_ROOT_NODE,
            self::NORMALIZER_TYPE_NODE
        );

        $defaultConfiguration = [
            self::CHAR_FILTER_TYPE_NODE => $charFilters,
            self::FILTER_TYPE_NODE      => $filters,
            self::ANALYZER_TYPE_NODE    => $analyzers,
        ];

        if (!empty($tokenizers)) {
            $defaultConfiguration[self::TOKENIZER_TYPE_NODE] = $tokenizers;
        }

        if (!empty($normalizers)) {
            $defaultConfiguration[self::NORMALIZER_TYPE_NODE] = $normalizers;
        }

        return $defaultConfiguration;
    }

    /**
     * Return configuration for a given language.
     *
     * @param \DOMXPath $xpath         XPath access to the document parsed.
     * @param string    $language      Current language.
     * @param array     $defaultConfig Default configuration available for all languages.
     *
     * @return array
     */
    private function getLanguageConfiguration(\DOMXPath $xpath, $language, array $defaultConfig)
    {
        $languageCharFilters = $this->parseFilters(
            $xpath,
            self::CHAR_FILTER_TYPE_ROOT_NODE,
            self::CHAR_FILTER_TYPE_NODE,
            $language
        );
        $charFilters = array_merge($defaultConfig[self::CHAR_FILTER_TYPE_NODE], $languageCharFilters);

        $languageFilters = $this->parseFilters(
            $xpath,
            self::FILTER_TYPE_ROOT_NODE,
            self::FILTER_TYPE_NODE,
            $language
        );
        $filters = array_merge($defaultConfig[self::FILTER_TYPE_NODE], $languageFilters);

        $tokenizers = $this->parseFilters(
            $xpath,
            self::TOKENIZER_TYPE_ROOT_NODE,
            self::TOKENIZER_TYPE_NODE,
            $language
        );

        if (!empty($defaultConfig[self::TOKENIZER_TYPE_NODE])) {
            $tokenizers = array_merge($defaultConfig[self::TOKENIZER_TYPE_NODE], $tokenizers);
        }

        $charFilterKeys = array_keys($charFilters);
        $filterKeys = array_keys($filters);
        $analyzers = $this->parseAnalyzers(
            $xpath,
            $charFilterKeys,
            $filterKeys,
            $language
        );
        $normalizers = $this->parseAnalyzers(
            $xpath,
            $charFilterKeys,
            $filterKeys,
            $language,
            self::NORMALIZER_TYPE_ROOT_NODE,
            self::NORMALIZER_TYPE_NODE
        );

        $defaultConfiguration = [
            self::CHAR_FILTER_TYPE_NODE => $charFilters,
            self::FILTER_TYPE_NODE      => $filters,
            self::ANALYZER_TYPE_NODE    => $analyzers,
        ];

        if (!empty($tokenizers)) {
            $defaultConfiguration[self::TOKENIZER_TYPE_NODE] = $tokenizers;
        }

        if (!empty($normalizers)) {
            $defaultConfiguration[self::NORMALIZER_TYPE_NODE] = $normalizers;
        }

        return $defaultConfiguration;
    }

    /**
     * Parse languages available in the document.
     *
     * @param \DOMXPath $xpath XPath access to the document parsed.
     *
     * @return array
     */
    private function getAllLanguages(\DOMXPath $xpath)
    {
        $languages = [];

        foreach ($xpath->query('//*[@language]') as $currentNode) {
            $languages[] = $currentNode->getAttribute('language');
        }

        return array_unique($languages);
    }

    /**
     * Filters parser by language.
     *
     * @param \DOMXPath $xpath        XPath access to the document parsed.
     * @param string    $rootNodeName Parsing root node.
     * @param string    $nodeName     Name of the nodes look up.
     * @param string    $language     Language searched.
     *
     * @return array
     */
    private function parseFilters(\DOMXPath $xpath, $rootNodeName, $nodeName, $language = self::LANGUAGE_DEFAULT)
    {
        $filters = [];
        $languagePath = sprintf("[@language='%s']", $language);
        $searchPath   = sprintf("/%s/%s/%s%s", self::ROOT_NODE_NAME, $rootNodeName, $nodeName, $languagePath);
        $filterNodes = $xpath->query($searchPath);
        foreach ($filterNodes as $filterNode) {
            $filterName = $filterNode->getAttribute('name');
            $filter     = ['type' => $filterNode->getAttribute('type')];
            foreach ($filterNode->childNodes as $childNode) {
                if ($childNode instanceof \DOMElement) {
                    try {
                        $filter[$childNode->tagName] = $this->jsonDecoder->decode($childNode->nodeValue);
                    } catch (\Exception $exception) {
                        $filter[$childNode->tagName] = $childNode->nodeValue;
                    }
                }
            }
            $filters[$filterName] = $filter;
        }

        return $filters;
    }

    /**
     * Analyzers parser by language.
     *
     * @param \DOMXPath $xpath                XPath access to the document parsed.
     * @param array     $availableCharFilters List of available char filters.
     * @param array     $availableFilters     List of available filters.
     * @param string    $language             Language searched.
     * @param string    $typeRootNode         Type root node name.
     * @param string    $typeNode             Type sub-node name.
     *
     * @return array
     */
    private function parseAnalyzers(
        \DOMXPath $xpath,
        array $availableCharFilters,
        array $availableFilters,
        $language = self::LANGUAGE_DEFAULT,
        $typeRootNode = self::ANALYZER_TYPE_ROOT_NODE,
        $typeNode = self::ANALYZER_TYPE_NODE
    ) {
        $analyzers = [];

        $languagePath = "@language='default'";

        if ($language != self::LANGUAGE_DEFAULT) {
            $languagePath .= " or @language='{$language}'";
        }

        $searchPath = sprintf(
            '/%s/%s/%s[%s]',
            self::ROOT_NODE_NAME,
            $typeRootNode,
            $typeNode,
            $languagePath
        );

        $analyzerNodes = $xpath->query($searchPath);

        foreach ($analyzerNodes as $analyzerNode) {
            $analyzer = [];
            $analyzerName = $analyzerNode->getAttribute('name');
            $analyzerTokenizer = $analyzerNode->getAttribute('tokenizer');
            $analyzerNormalizer = $analyzerNode->getAttribute('normalizer');

            if ($analyzerTokenizer) {
                $analyzer['tokenizer'] = $analyzerTokenizer;
            }

            if ($analyzerNormalizer) {
                $analyzer['normalizer'] = $analyzerNormalizer;
            }

            $analyzer['type'] = 'custom';
            $analyzers[$analyzerName] = $analyzer;

            $filterPath = sprintf('%s/%s', self::FILTER_TYPE_ROOT_NODE, self::FILTER_TYPE_NODE);
            $analyzer[self::FILTER_TYPE_NODE] = $this->getFiltersByRef(
                $xpath,
                $analyzerNode,
                $filterPath,
                $availableFilters
            );

            $charFilterPath = sprintf('%s/%s', self::CHAR_FILTER_TYPE_ROOT_NODE, self::CHAR_FILTER_TYPE_NODE);
            $analyzer[self::CHAR_FILTER_TYPE_NODE] = $this->getFiltersByRef(
                $xpath,
                $analyzerNode,
                $charFilterPath,
                $availableCharFilters
            );

            $analyzers[$analyzerName] = $analyzer;
        }

        return $analyzers;
    }
    /**
     * Return all filters under a root node filtered by an array of available filters.
     *
     * @param \DOMXPath $xpath            XPath access to the document parsed.
     * @param \DomNode  $rootNode         Search root node.
     * @param string    $searchPath       Filters search path.
     * @param array     $availableFilters List of available filters.
     *
     * @return array
     */
    private function getFiltersByRef(\DOMXPath $xpath, \DomNode $rootNode, $searchPath, array $availableFilters)
    {
        $filters     = [];
        $filterNodes = $xpath->query($searchPath, $rootNode);

        foreach ($filterNodes as $filterNode) {
            $filterName = $filterNode->getAttribute('ref');
            if (in_array($filterName, $availableFilters)) {
                $filters[] = $filterName;
            }
        }

        return $filters;
    }

    /**
     * Return all default language stemmers as defined currently in config.
     * Relieson the fact that the filter used will be of type stemmer and named stemmer.
     *
     * @param \DOMXPath $xpath XPath access to the document parsed.
     *
     * @return array
     */
    private function getAllDefaultLanguageStemmers(\DOMXPath $xpath)
    {
        $defaultStemmers = [];

        $filterPath = "@type='stemmer' and @name='stemmer'";
        $stemmerFiltersPath = sprintf(
            "/%s/%s/%s[%s]",
            self::ROOT_NODE_NAME,
            self::FILTER_TYPE_ROOT_NODE,
            self::FILTER_TYPE_NODE,
            $filterPath
        );

        $stemmerFilterNodes = $xpath->query($stemmerFiltersPath);
        foreach ($stemmerFilterNodes as $stemmerFilterNode) {
            $language = $stemmerFilterNode->getAttribute('language');
            $stemmer = false;
            foreach ($stemmerFilterNode->childNodes as $childNode) {
                if ($childNode instanceof \DOMElement) {
                    if ($childNode->tagName === 'language') {
                        $stemmer = $childNode->nodeValue;
                        break;
                    }
                }
            }
            if (!empty($stemmer)) {
                $defaultStemmers[$language] = $stemmer;
            }
        }

        return $defaultStemmers;
    }

    /**
     * Parse all stemmers options available for language that support multiple stemmers.
     *
     * @param \DOMXPath $xpath           XPath access to the document parsed.
     * @param array     $defaultStemmers Default stemmers for available languages.
     *
     * @return array
     */
    private function getAllStemmersOptions(\DOMXPath $xpath, $defaultStemmers = [])
    {
        $stemmerOptions = [];

        $searchPath = sprintf(
            "/%s/%s/%s",
            self::ROOT_NODE_NAME,
            self::STEMMER_TYPE_ROOT_NODE,
            self::STEMMER_GROUP_TYPE_NODE
        );
        $stemmerGroupNodes = $xpath->query($searchPath);
        foreach ($stemmerGroupNodes as $stemmerGroupNode) {
            $languageCode = $stemmerGroupNode->getAttribute('language');
            $languageTitle = $stemmerGroupNode->getAttribute('title');
            $stemmerOptions[$languageCode] = [
                'identifier' => $languageCode,
                'title' => $languageTitle,
                'stemmers' => [],
            ];

            $stemmerOptions[$languageCode]['stemmers'] = $this->getLanguageStemmers(
                $xpath,
                $stemmerGroupNode,
                $defaultStemmers[$languageCode] ?: null
            );
        }

        return $stemmerOptions;
    }

    /**
     * Parse available stemmers for a given language.
     *
     * @param \DOMXPath   $xpath          XPath access to the document parsed.
     * @param \DomNode    $rootNode       Stemmers group node for a given language.
     * @param string|null $defaultStemmer Default stemme for the given language, if defined.
     *
     * @return array
     */
    private function getLanguageStemmers(\DOMXPath $xpath, \DomNode $rootNode, $defaultStemmer = null)
    {
        $stemmers = [];

        $searchPath = sprintf("./%s", self::STEMMER_TYPE_NODE);
        $stemmerNodes = $xpath->query($searchPath, $rootNode);
        foreach ($stemmerNodes as $stemmerNode) {
            $identifier = $stemmerNode->getAttribute('identifier');
            $stemmer    = [
                'identifier'    => $identifier,
                'recommended'   => $stemmerNode->getAttribute('recommended') ?: false,
                'default'       => ($identifier === $defaultStemmer),
            ];
            foreach ($stemmerNode->childNodes as $childNode) {
                if ($childNode instanceof \DOMElement) {
                    if ($childNode->tagName === 'label') {
                        $stemmer['label'] = $childNode->nodeValue;
                        continue;
                    }

                    try {
                        $stemmer[$childNode->tagName] = $this->jsonDecoder->decode($childNode->nodeValue);
                    } catch (\Exception $exception) {
                        $stemmer[$childNode->tagName] = $childNode->nodeValue;
                    }
                }
            }

            $stemmers[$identifier] = $stemmer;
        }

        return $stemmers;
    }
}
