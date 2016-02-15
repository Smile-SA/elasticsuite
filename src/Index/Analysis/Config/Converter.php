<?php
/**
 *
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile_ElasticSuite
 * @package   Smile\ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Index\Analysis\Config;

class Converter implements \Magento\Framework\Config\ConverterInterface
{
    const ROOT_NODE_NAME = 'analysis';

    const CHAR_FILTER_TYPE_ROOT_NODE = 'char_filters';
    const CHAR_FILTER_TYPE_NODE = 'char_filter';

    const FILTER_TYPE_ROOT_NODE = 'filters';
    const FILTER_TYPE_NODE = 'filter';

    const ANALYZER_TYPE_ROOT_NODE = 'analyzers';
    const ANALYZER_TYPE_NODE = 'analyzer';

    /**
     * Convert dom node tree to array
     *
     * @param mixed $source
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

    private function getDefaultConfiguration($xpath)
    {
        $charFilters = $this->parseFilters($xpath, self::CHAR_FILTER_TYPE_ROOT_NODE, self::CHAR_FILTER_TYPE_NODE);
        $filters     = $this->parseFilters($xpath, self::FILTER_TYPE_ROOT_NODE, self::FILTER_TYPE_NODE);
        $analyzers   = $this->parseAnalyzers($xpath, array_keys($charFilters), array_keys($filters));

        $defaultConfiguration = [
            self::CHAR_FILTER_TYPE_ROOT_NODE => $charFilters,
            self::FILTER_TYPE_ROOT_NODE      => $filters,
            self::ANALYZER_TYPE_NODE         => $analyzers,
        ];

        return $defaultConfiguration;
    }

    private function getLanguageConfiguration($xpath, $language, $defaultConfig)
    {
        $languageCharFilters = $this->parseFilters(
            $xpath,
            self::CHAR_FILTER_TYPE_ROOT_NODE,
            self::CHAR_FILTER_TYPE_NODE,
            $language
        );
        $charFilters         = array_merge($defaultConfig[self::CHAR_FILTER_TYPE_ROOT_NODE], $languageCharFilters);

        $languageFilters = $this->parseFilters($xpath, self::FILTER_TYPE_ROOT_NODE, self::FILTER_TYPE_NODE, $language);
        $filters         =  array_merge($defaultConfig[self::FILTER_TYPE_ROOT_NODE], $languageFilters);

        $analyzers   = $this->parseAnalyzers($xpath, array_keys($charFilters), array_keys($filters), $language);

        $defaultConfiguration = [
            self::CHAR_FILTER_TYPE_ROOT_NODE => $charFilters,
            self::FILTER_TYPE_ROOT_NODE      => $filters,
            self::ANALYZER_TYPE_NODE         => $analyzers,
        ];

        return $defaultConfiguration;
    }

    private function getAllLanguages($xpath)
    {
        $languages = [];

        foreach ($xpath->query('//*[@language]') as $currentNode) {
            $languages[] = $currentNode->getAttribute('language');
        }

        return array_unique($languages);
    }

    private function parseFilters($xpath, $rootNodeName, $nodeName, $language = 'default')
    {
        $filters = [];
        $languagePath = "[@language='${language}']";
        $searchPath   = sprintf("/%s/%s/%s%s", self::ROOT_NODE_NAME, $rootNodeName, $nodeName, $languagePath);
        $filterNodes = $xpath->query($searchPath);
        foreach ($filterNodes as $filterNode) {
            $filterName = $filterNode->getAttribute('name');
            $filter     = ['type' => $filterNode->getAttribute('type')];
            foreach ($filterNode->childNodes as $childNode) {
                if ($childNode instanceof \DOMElement) {
                    $value = $childNode->nodeValue;
                    $filter[$childNode->tagName] = $childNode->nodeValue;
                }
            }
            $filters[$filterName] = $filter;
        }
        return $filters;
    }

    private function parseAnalyzers(\DOMXPath $xpath, $availableCharFilters, $availableFilters, $language = 'default')
    {
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
            $analyzer = ['tokenizer' => $analyzerNode->getAttribute('tokenizer')];
            $analyzers[$analyzerName] = $analyzer;

            $filterPath = sprintf('%s/%s', self::FILTER_TYPE_ROOT_NODE, self::FILTER_TYPE_NODE);
            $analyzer[self::FILTER_TYPE_ROOT_NODE] = $this->getFilterByRef(
                $xpath,
                $analyzerNode,
                $filterPath,
                $availableFilters
            );

            $charFilterPath = sprintf('%s/%s', self::CHAR_FILTER_TYPE_ROOT_NODE, self::CHAR_FILTER_TYPE_NODE);
            $analyzer[self::CHAR_FILTER_TYPE_ROOT_NODE] = $this->getFilterByRef(
                $xpath,
                $analyzerNode,
                $charFilterPath,
                $availableCharFilters
            );

            $analyzers[$analyzerName] = $analyzer;
        }

        return $analyzers;
    }

    private function getFilterByRef($xpath, $rootNode, $searchPath, $availableFilters)
    {
        $filters = [];
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
