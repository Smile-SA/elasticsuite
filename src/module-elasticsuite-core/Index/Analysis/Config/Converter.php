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
    const ANALYZER_TYPE_ROOT_NODE    = 'analyzers';
    const ANALYZER_TYPE_NODE         = 'analyzer';
    const NORMALIZER_TYPE_ROOT_NODE  = 'normalizers';
    const NORMALIZER_TYPE_NODE       = 'normalizer';
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
}
