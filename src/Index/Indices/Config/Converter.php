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
namespace Smile\ElasticSuiteCore\Index\Indices\Config;

use Smile\ElasticSuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;

class Converter implements \Magento\Framework\Config\ConverterInterface
{
    const ROOT_NODE_NAME          = 'indices';
    const INDEX_NODE_TYPE         = 'index';
    const TYPE_NODE_TYPE          = 'type';
    const MAPPING_NODE_TYPE       = 'mapping';
    const MAPPING_FIELD_NODE_TYPE = 'field';
    const DATASOURCES_PATH        = 'datasources/datasource';

    /**
     * Convert dom node tree to array
     *
     * @param mixed $source
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

    private function parseIndexConfig($xpath, $indexRootNode)
    {
        $indexConfig = ['types' => []];
        $typesSearchPath = sprintf('%s', self::TYPE_NODE_TYPE);
        $xpath->query($typesSearchPath, $indexRootNode);

        foreach ($xpath->query($typesSearchPath, $indexRootNode) as $typeNode) {
            $typeParams = $this->parseTypeConfig($xpath, $typeNode);
            $indexConfig['types'][$typeNode->getAttribute('name')] = $typeParams;
        }

        return $indexConfig;
    }

    private function parseTypeConfig($xpath, $typeRootNode)
    {
        $staticFields  = $this->parseMappingFields($xpath, $typeRootNode);
        $datasources = $this->parseDatasources($xpath, $typeRootNode);

        $dynamicFieldProviders = array_filter(
            $datasources,
            function ($datasource) {
                return $datasource instanceof DynamicFieldProviderInterface;
            }
        );

        $mappingParams = ['staticFields' => $staticFields, 'dynamicFieldProviders' => $datasources];

        return ['mapping' => $mappingParams, 'datasources' => $datasources];
    }

    private function parseMappingFields($xpath, $typeRootNode)
    {
        $fields = [];
        $fieldSearchPath = sprintf('%s/%s', self::MAPPING_NODE_TYPE, self::MAPPING_FIELD_NODE_TYPE);

        foreach ($xpath->query($fieldSearchPath, $typeRootNode) as $fieldName => $fieldNode) {
            $fields[$fieldNode->getAttribute('name')] = $this->createMappingField($fieldNode);
        }

        return $fields;
    }

    private function parseDatasources($xpath, $typeRootNode)
    {
        $datasources = [];
        foreach ($xpath->query(self::DATASOURCES_PATH, $typeRootNode) as $datasourceNode) {
            $datasources[$datasourceNode->getAttribute('name')] = $datasourceNode->nodeValue;
        }
        return $datasources;
    }

    private function createMappingField($fieldNode)
    {
        $fieldParam = ['type' => $fieldNode->getAttribute('type')];
        if ($fieldNode->hasAttribute('nestedPath')) {
            $fieldParam['nestedPath'] = $fieldNode->getAttribute('nestedPath');
        }
        foreach ($fieldNode->childNodes as $childNode) {
            if ($childNode instanceof \DOMElement) {
                $fieldParam[$childNode->tagName] = $childNode->nodeValue;
            }
        }

        return $fieldParam;
    }
}
