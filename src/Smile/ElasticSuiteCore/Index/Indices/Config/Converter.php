<?php

namespace Smile\ElasticSuiteCore\Index\Indices\Config;

class Converter implements \Magento\Framework\Config\ConverterInterface
{
    const ROOT_NODE_NAME          = 'indices';
    const INDEX_NODE_TYPE         = 'index';
    const TYPE_NODE_TYPE          = 'type';
    const MAPPING_NODE_TYPE       = 'mapping';
    const MAPPING_FIELD_NODE_TYPE = 'field';

    private $defaultFieldConfig = [
        'is_searchable'           => false,
        'used_in_spellcheck'      => false,
        'used_in_autocomplete'    => false,
        'search_weight'           => 1,
        'is_filterable'           => false,
        'is_filterable_in_search' => false,
    ];

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
            $typeName = $typeNode->getAttribute('name');
            $indexConfig['types'][$typeName] = $this->parseTypeConfig($xpath, $typeNode);
        }

        return $indexConfig;
    }

    private function parseTypeConfig($xpath, $typeRootNode)
    {
        return [
            'mapping' => ['fields' => $this->parseMappingFields($xpath, $typeRootNode)]
        ];
    }

    private function parseMappingFields($xpath, $typeRootNode)
    {
        $fields = [];
        $fieldSearchPath = sprintf('%s/%s', self::MAPPING_NODE_TYPE, self::MAPPING_FIELD_NODE_TYPE);

        foreach ($xpath->query($fieldSearchPath, $typeRootNode) as $fieldNode) {
            $fieldName = $fieldNode->getAttribute('name');
            $fields[$fieldName] = ['type' => $fieldNode->getAttribute('type')] + $this->defaultFieldConfig;

            foreach ($fieldNode->childNodes as $childNode) {
                if ($childNode instanceof \DOMElement) {
                    if ($childNode->tagName == 'search_weight') {
                        $fields[$fieldName][$childNode->tagName] = (int) $childNode->nodeValue;
                    } else {
                        $fields[$fieldName][$childNode->tagName] = (bool) $childNode->nodeValue;
                    }
                }
            }
        }

        return $fields;
    }
}
