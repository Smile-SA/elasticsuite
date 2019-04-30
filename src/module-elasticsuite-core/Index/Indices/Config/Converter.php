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

namespace Smile\ElasticsuiteCore\Index\Indices\Config;

use Smile\ElasticsuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;

/**
 * Convert indices configuration XML file.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Converter implements \Magento\Framework\Config\ConverterInterface
{
    const ROOT_NODE_NAME          = 'indices';
    const INDEX_NODE_TYPE         = 'index';
    const TYPE_NODE_TYPE          = 'type';
    const MAPPING_NODE_TYPE       = 'mapping';
    const MAPPING_FIELD_NODE_TYPE = 'field';
    const DATASOURCES_PATH        = 'datasources/datasource';

    /**
     * Tag names underscore transformation cache
     *
     * @var array
     */
    private $underscoreCache = [];

    /**
     * Convert dom node tree to array
     *
     * @param mixed $source Configuration XML source.
     *
     * @return array
     */
    public function convert($source)
    {
        $indices = [];

        $xpath = new \DOMXPath($source);
        $indexSearchPath = sprintf("/%s/%s", self::ROOT_NODE_NAME, self::INDEX_NODE_TYPE);

        foreach ($xpath->query($indexSearchPath) as $indexNode) {
            $indexIdentifier = $indexNode->getAttribute('identifier');
            $indices[$indexIdentifier] = $this->parseIndexConfig($xpath, $indexNode);
        }

        return $indices;
    }

    /**
     * Parse index node configuration.
     *
     * @param \DOMXPath $xpath         XPath access to the document parsed.
     * @param \DOMNode  $indexRootNode Index node to be parsed.
     *
     * @return array
     */
    private function parseIndexConfig(\DOMXPath $xpath, \DOMNode $indexRootNode)
    {
        $indexConfig = ['types' => []];
        $typesSearchPath = sprintf('%s', self::TYPE_NODE_TYPE);
        $xpath->query($typesSearchPath, $indexRootNode);

        foreach ($xpath->query($typesSearchPath, $indexRootNode) as $typeNode) {
            $typeParams = $this->parseTypeConfig($xpath, $typeNode);
            $indexConfig['types'][$typeNode->getAttribute('name')] = $typeParams;
        }

        $indexConfig['defaultSearchType'] = $indexRootNode->getAttribute('defaultSearchType');

        return $indexConfig;
    }

    /**
     * Parse type node configuration.
     *
     * @param \DOMXPath $xpath        XPath access to the document parsed.
     * @param \DOMNode  $typeRootNode Type node to be parsed.
     *
     * @return array
     */
    private function parseTypeConfig(\DOMXPath $xpath, \DOMNode $typeRootNode)
    {
        $staticFields  = $this->parseMappingFields($xpath, $typeRootNode);
        $idFieldName   = $typeRootNode->getAttribute('idFieldName');
        $datasources   = $this->parseDatasources($xpath, $typeRootNode);

        $mappingParams = ['staticFields' => $staticFields];

        return ['mapping' => $mappingParams, 'idFieldName' => $idFieldName, 'datasources' => $datasources];
    }

    /**
     * Parse type fields from type node configuration.
     *
     * @param \DOMXPath $xpath        XPath access to the document parsed.
     * @param \DOMNode  $typeRootNode Type node to be parsed.
     *
     * @return array
     */
    private function parseMappingFields(\DOMXPath $xpath, \DOMNode $typeRootNode)
    {
        $fields = [];
        $fieldSearchPath = sprintf('%s/%s', self::MAPPING_NODE_TYPE, self::MAPPING_FIELD_NODE_TYPE);

        foreach ($xpath->query($fieldSearchPath, $typeRootNode) as $fieldNode) {
            $fields[$fieldNode->getAttribute('name')] = $this->createMappingField($fieldNode);
        }

        return $fields;
    }

    /**
     * Parse datasources from type node configuration.
     *
     * @deprecated
     *
     * @param \DOMXPath $xpath        XPath access to the document parsed.
     * @param \DOMNode  $typeRootNode Type node to be parsed.
     *
     * @return array
     */
    private function parseDatasources(\DOMXPath $xpath, \DOMNode $typeRootNode)
    {
        $datasources = [];

        foreach ($xpath->query(self::DATASOURCES_PATH, $typeRootNode) as $datasourceNode) {
            $datasources[$datasourceNode->getAttribute('name')] = $datasourceNode->nodeValue;
        }

        return $datasources;
    }

    /**
     * Parse field configuration params.
     *
     * @param \DOMNode $fieldNode Field node to be parsed.
     *
     * @return array
     */
    private function createMappingField(\DOMNode $fieldNode)
    {
        $fieldParam = ['type' => $fieldNode->getAttribute('type')];

        if ($fieldNode->hasAttribute('nestedPath')) {
            $fieldParam['nestedPath'] = $fieldNode->getAttribute('nestedPath');
        }

        foreach ($fieldNode->childNodes as $childNode) {
            if ($childNode instanceof \DOMElement) {
                $tagName = $this->underscore($childNode->tagName);
                $fieldParam['fieldConfig'][$tagName] = $childNode->nodeValue;
            }
        }

        return $fieldParam;
    }

    /**
     * Converts tag name from camelCase to snake_case
     *
     * isSearchable === is_searchable
     * Uses cache to eliminate unnecessary preg_replace
     *
     * @param string $name The name to transform
     *
     * @return string
     */
    private function underscore($name)
    {
        if (!isset($this->underscoreCache[$name])) {
            $this->underscoreCache[$name] = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $name));
        }

        return $this->underscoreCache[$name];
    }
}
