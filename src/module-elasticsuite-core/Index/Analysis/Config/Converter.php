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
        $analyzers   = $this->parseAnalyzers($xpath, array_keys($charFilters), array_keys($filters));

        $defaultConfiguration = [
            self::CHAR_FILTER_TYPE_NODE => $charFilters,
            self::FILTER_TYPE_NODE      => $filters,
            self::ANALYZER_TYPE_NODE    => $analyzers,
        ];

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

        $analyzers = $this->parseAnalyzers($xpath, array_keys($charFilters), array_keys($filters), $language);

        $defaultConfiguration = [
            self::CHAR_FILTER_TYPE_NODE => $charFilters,
            self::FILTER_TYPE_NODE      => $filters,
            self::ANALYZER_TYPE_NODE    => $analyzers,
        ];

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
    private function parseFilters(\DOMXPath $xpath, $rootNodeName, $nodeName, $language = 'default')
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
                    } catch (\Exception $e) {
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
     *
     * @return array
     */
    private function parseAnalyzers(
        \DOMXPath $xpath,
        array $availableCharFilters,
        array $availableFilters,
        $language = 'default'
    ) {
        $analyzers = [];

        $languagePath = "@language='default'";

        if ($language != 'default') {
            $languagePath .= " or @language='{$language}'";
        }

        $searchPath = sprintf(
            '/%s/%s/%s[%s]',
            self::ROOT_NODE_NAME,
            self::ANALYZER_TYPE_ROOT_NODE,
            self::ANALYZER_TYPE_NODE,
            $languagePath
        );

        $analyzerNodes = $xpath->query($searchPath);

        foreach ($analyzerNodes as $analyzerNode) {
            $analyzerName = $analyzerNode->getAttribute('name');
            $analyzer = ['tokenizer' => $analyzerNode->getAttribute('tokenizer'), 'type' => 'custom'];
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
