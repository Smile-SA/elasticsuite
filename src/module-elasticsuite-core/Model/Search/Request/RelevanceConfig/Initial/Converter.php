<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Initial;

use Magento\Framework\Config\ConverterInterface;

/**
 * Default Configuration converter
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Converter implements ConverterInterface
{
    /**
     * Node paths to process
     *
     * @var array
     */
    protected $nodeMap = [];

    /**
     * @var array
     */
    protected $metadata = [];

    /**
     * @param array $nodeMap The node map
     */
    public function __construct(array $nodeMap = [])
    {
        $this->nodeMap      = $nodeMap;
    }

    /**
     * Convert config
     *
     * @param \DOMDocument $source The source document
     *
     * @return array
     */
    public function convert($source)
    {
        $output = [];
        $xpath = new \DOMXPath($source);
        $this->metadata = [];

        /** @var $node \DOMNode */
        foreach ($xpath->query(implode(' | ', $this->nodeMap)) as $node) {
            $output = array_merge($output, $this->convertNode($node));
        }

        $result = ['data' => $output, 'metadata' => $this->metadata];

        return $result;
    }

    /**
     * Convert node oto array
     *
     * @param \DOMNode $node The Configuration DOM node to parse
     * @param string   $path The path for DOM navigation
     *
     * @return array|string|null
     */
    protected function convertNode(\DOMNode $node, $path = '')
    {
        $output = [];
        if ($node->nodeType == XML_ELEMENT_NODE) {
            $nodeName = $this->parseNodeName($node);
            $output[$nodeName] = $this->parseNodeData($node, $path);
        } elseif ($node->nodeType == XML_CDATA_SECTION_NODE
            || $node->nodeType == XML_TEXT_NODE && trim($node->nodeValue) != '') {
            return $node->nodeValue;
        }

        return $output;
    }

    /**
     * Retrieve proper node name
     *
     * @param \DOMNode $node The configuration XML node to parse
     *
     * @return string
     */
    protected function parseNodeName($node)
    {
        $nodeName = $node->nodeName;
        if ($node->hasAttributes()) {
            $storeCodeNode     = $node->attributes->getNamedItem('store_code');
            $containerNameNode = $node->attributes->getNamedItem('name');
            if ($containerNameNode) {
                $nodeName = $containerNameNode->nodeValue;
                if ($storeCodeNode) {
                    $nodeName = $nodeName . "|" . $storeCodeNode->nodeValue;
                }
            }
        }

        return $nodeName;
    }

    /**
     * Convert node onto array
     *
     * @param \DOMNode $node The Configuration DOM node to parse
     * @param string   $path The path for DOM navigation
     *
     * @return array|string|null
     */
    private function parseNodeData(\DOMNode $node, $path)
    {
        $nodeData = [];

        foreach ($node->childNodes as $childNode) {
            $childrenData = $this->convertNode($childNode, ($path ? $path . '/' : '') . $childNode->nodeName);
            if ($childrenData === null) {
                continue;
            }
            $nodeData = $this->mergeChildData($nodeData, $childrenData);
        }

        if (is_array($nodeData) && empty($nodeData)) {
            $nodeData = null;
        }

        return $nodeData;
    }

    /**
     * Merge child node data into current node
     *
     * @param array $nodeData     Current node data
     * @param array $childrenData The child node data
     *
     * @return array
     */
    private function mergeChildData($nodeData, $childrenData)
    {
        if (is_array($childrenData)) {
            $nodeData = array_merge($nodeData, $childrenData);

            return $nodeData;
        }

        $nodeData = $childrenData;

        return $nodeData;
    }
}
