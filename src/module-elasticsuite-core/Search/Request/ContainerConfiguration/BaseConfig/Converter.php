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
     * @var string
     */
    const FILTERS_PATH = 'filters/filter';

    /**
     * @var string
     */
    const AGGREGATIONS_PATH = 'aggregations/aggregation';

    /**
     * @var string
     */
    const AGGREGATIONS_PROVIDERS_PATH = 'aggregations/provider';

    /**
     * @var string
     */
    const AGGREGATION_FILTERS_PATH = 'filters/filter';

    /**
     * @var string
     */
    const METRICS_PATH = 'metrics/metric';

    /**
     * @var string
     */
    const PIPELINES_PATH = 'pipelines/pipeline';

    /**
     * @var string
     */
    const PIPELINE_SIMPLE_BUCKETS_PATH = 'bucketsPath/path';

    /**
     * @var string
     */
    const PIPELINE_COMPLEX_BUCKETS_PATH = 'bucketsPath/mapping';

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
        $xpath        = new \DOMXPath($source);
        $requests     = [];

        foreach ($requestNodes as $requestNode) {
            $simpleXmlNode = simplexml_import_dom($requestNode);
            /** @var \DOMElement $requestNode */
            $name                             = $requestNode->getAttribute('name');
            $request                          = $this->mergeAttributes((array) $simpleXmlNode);
            $request['filters']               = $this->parseFilters($xpath, $requestNode);
            $request['aggregations']          = $this->parseAggregations($xpath, $requestNode);
            $request['aggregationsProviders'] = $this->parseAggregationsProviders($xpath, $requestNode);
            $requests[$name]                  = $request;
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

    /**
     * Parse filters from request node configuration.
     *
     * @param \DOMXPath $xpath           XPath access to the document parsed.
     * @param \DOMNode  $requestRootNode Request node to be parsed.
     *
     * @return array
     */
    private function parseFilters(\DOMXPath $xpath, \DOMNode $requestRootNode)
    {
        $filters = [];

        foreach ($xpath->query(self::FILTERS_PATH, $requestRootNode) as $filterNode) {
            $filters[$filterNode->getAttribute('name')] = $filterNode->nodeValue;
        }

        return $filters;
    }

    /**
     * Parse aggregations from request node configuration.
     *
     * @param \DOMXPath $xpath    XPath access to the document parsed.
     * @param \DOMNode  $rootNode Request node to be parsed.
     *
     * @return array
     */
    private function parseAggregations(\DOMXPath $xpath, \DOMNode $rootNode)
    {
        $aggs = [];

        foreach ($xpath->query(self::AGGREGATIONS_PATH, $rootNode) as $aggNode) {
            $bucketName   = $aggNode->getAttribute('name');
            $bucketConfig = [];

            foreach ($aggNode->attributes as $attribute) {
                $bucketConfig[$attribute->name] = $attribute->value;
            }
            $aggFilters = $this->parseAggregationFilters($xpath, $aggNode);
            if (!empty($aggFilters)) {
                $bucketConfig['filters'] = $aggFilters;
            }
            $bucketConfig['childBuckets'] = $this->parseAggregations($xpath, $aggNode);
            $bucketConfig['metrics'] = $this->parseMetrics($xpath, $aggNode);
            $bucketConfig['pipelines'] = $this->parsePipelines($xpath, $aggNode);
            $aggs[$bucketName] = $bucketConfig;
        }

        return $aggs;
    }

    /**
     * Parse aggregations providers from request node configuration.
     *
     * @param \DOMXPath $xpath    XPath access to the document parsed.
     * @param \DOMNode  $rootNode Request node to be parsed.
     *
     * @return array
     */
    private function parseAggregationsProviders(\DOMXPath $xpath, \DOMNode $rootNode)
    {
        $providers = [];

        foreach ($xpath->query(self::AGGREGATIONS_PROVIDERS_PATH, $rootNode) as $providerNode) {
            $providers[$providerNode->getAttribute('name')] = $providerNode->nodeValue;
        }

        return $providers;
    }

    /**
     * Parse filters from a bucket.
     *
     * @param \DOMXPath $xpath    XPath access to the document parsed.
     * @param \DOMNode  $rootNode Aggregation node to be parsed.
     *
     * @return null|string
     */
    private function parseAggregationFilters(\DOMXPath $xpath, \DOMNode $rootNode)
    {
        $filters = [];

        foreach ($xpath->query(self::AGGREGATION_FILTERS_PATH, $rootNode) as $filterNode) {
            $filters[$filterNode->getAttribute('name')] = $filterNode->nodeValue;
        }

        return $filters;
    }

    /**
     * Parse metrics from a bucket.
     *
     * @param \DOMXPath $xpath    XPath access to the document parsed.
     * @param \DOMNode  $rootNode Aggregation node to be parsed.
     *
     * @return array
     */
    private function parseMetrics(\DOMXPath $xpath, \DOMNode $rootNode)
    {
        $metrics = [];

        foreach ($xpath->query(self::METRICS_PATH, $rootNode) as $metricNode) {
            $metric = [];
            foreach ($metricNode->attributes as $attribute) {
                $metric[$attribute->name] = $attribute->value;
            }
            $metrics[$metric['name']] = $metric;
        }

        return $metrics;
    }

    /**
     * Parse pipelines from a bucket.
     *
     * @param \DOMXPath $xpath    XPath access to the document parsed.
     * @param \DOMNode  $rootNode Aggregation node to be parsed for pipeline aggregations.
     *
     * @return array
     */
    private function parsePipelines(\DOMXPath $xpath, \DOMNode $rootNode)
    {
        $pipelines = [];

        foreach ($xpath->query(self::PIPELINES_PATH, $rootNode) as $pipelineNode) {
            $pipeline = [];
            foreach ($pipelineNode->attributes as $attribute) {
                $pipeline[$attribute->name] = $attribute->value;
            }
            $pipeline['bucketsPath'] = $this->parsePipelineBucketsPath($xpath, $pipelineNode);
            $pipelines[$pipeline['name']] = $pipeline;
        }

        return $pipelines;
    }

    /**
     * Parse the buckets path of a pipeline aggregation.
     *
     * @param \DOMXPath $xpath        XPath access to the document parsed.
     * @param \DOMNode  $pipelineNode Pipeline aggregation node to be parsed for buckets path.
     *
     * @return null|array|string
     */
    private function parsePipelineBucketsPath(\DOMXPath $xpath, \DOMNode $pipelineNode)
    {
        $bucketsPath = null;

        foreach ($xpath->query(self::PIPELINE_COMPLEX_BUCKETS_PATH, $pipelineNode) as $bucketsPathMappingNode) {
            $paramName = $bucketsPathMappingNode->getAttribute('paramName');
            $path      = $bucketsPathMappingNode->textContent;
            if (empty($paramName) || empty($path)) {
                continue;
            }
            $bucketsPath[$paramName] = $path;
        }

        if (empty($bucketsPath)) {
            foreach ($xpath->query(self::PIPELINE_SIMPLE_BUCKETS_PATH, $pipelineNode) as $bucketsPathNode) {
                $bucketsPath = $bucketsPathNode->nodeValue;
            }
        }

        return $bucketsPath;
    }
}
