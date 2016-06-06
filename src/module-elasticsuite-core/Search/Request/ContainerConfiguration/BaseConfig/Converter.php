<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration\BaseConfig;

/**
 * ElasticSuite search requests XML converter.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Converter extends \Magento\Framework\Search\Request\Config\Converter
{
    /**
     * Convert config.
     *
     * @param \DOMDocument $source XML file read.
     *
     * @return array
     */
    public function convert($source)
    {
        // Due to SimpleXML not deleting comment we have to strip them before using the source.
        $source = $this->stripComments($source);

        /** @var \DOMNodeList $requestNodes */
        $requestNodes = $source->getElementsByTagName('request');
        $requests = [];
        foreach ($requestNodes as $requestNode) {
            $simpleXmlNode = simplexml_import_dom($requestNode);
            /** @var \DOMElement $requestNode */
            $name = $requestNode->getAttribute('name');
            $request = $this->mergeAttributes((array) $simpleXmlNode);
            $requests[$name] = $request;
        }

        return $requests;
    }

    /**
     * This method remove all comments of an XML document.
     *
     * @param \DOMDocument $source Document to be cleansed.
     *
     * @return \DOMDocument
     */
    private function stripComments(\DOMDocument $source)
    {
        $xpath = new \DOMXPath($source);

        foreach ($xpath->query('//comment()') as $commentNode) {
            $commentNode->parentNode->removeChild($commentNode);
        }

        return $source;
    }
}
