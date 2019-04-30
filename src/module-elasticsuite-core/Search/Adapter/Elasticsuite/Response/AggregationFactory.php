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

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response;

/**
 * ElasticSuite aggregations response builder.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AggregationFactory
{
    /**
     * @var \Magento\Framework\Search\Response\AggregationFactory
     */
    private $aggregationFactory;

    /**
     * @var \Magento\Framework\Search\Response\BucketFactory
     */
    private $bucketFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\Aggregation\ValueFactory
     */
    private $valueFactory;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\Search\Response\AggregationFactory                                 $aggregationFactory Aggregation factory.
     * @param \Magento\Framework\Search\Response\BucketFactory                                      $bucketFactory      Bucket factory.
     * @param \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\Aggregation\ValueFactory $valueFactory       Value factory.
     */
    public function __construct(
        \Magento\Framework\Search\Response\AggregationFactory $aggregationFactory,
        \Magento\Framework\Search\Response\BucketFactory $bucketFactory,
        \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\Aggregation\ValueFactory $valueFactory
    ) {
        $this->aggregationFactory = $aggregationFactory;
        $this->bucketFactory      = $bucketFactory;
        $this->valueFactory       = $valueFactory;
    }

    /**
     * Build a aggregation object from the search engine response.
     *
     * @param array $rawAggregations ES aggregations response.
     *
     * @return \Magento\Framework\Search\Response\Aggregation
     */
    public function create(array $rawAggregations)
    {
        $buckets = $this->getBuckets($rawAggregations);

        return $this->aggregationFactory->create(['buckets' => $buckets]);
    }

    /**
     * Return buckets from an ES aggregation.
     *
     * @param array $rawAggregations ES aggregations.
     *
     * @return \Magento\Framework\Search\Response\Bucket[]
     */
    private function getBuckets($rawAggregations)
    {
        $buckets = [];

        foreach ($rawAggregations as $bucketName => $rawBucket) {
            while (isset($rawBucket[$bucketName])) {
                $rawBucket = $rawBucket[$bucketName];
            }

            $bucketParams = ['name' => $bucketName, 'values' => $this->getBucketValues($rawBucket)];
            $buckets[$bucketName] = $this->bucketFactory->create($bucketParams);
        }

        return $buckets;
    }

    /**
     * Return a bucket from an ES aggregation.
     *
     * @param array $rawBucket ES bucket.
     *
     * @return \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\Aggregation\Value[]
     */
    private function getBucketValues($rawBucket)
    {
        $values = [];

        if (isset($rawBucket['sum_other_doc_count']) && $rawBucket['sum_other_doc_count'] > 0) {
            $rawBucket['buckets']['__other_docs']['doc_count'] = $rawBucket['sum_other_doc_count'];
        }

        foreach ($rawBucket['buckets'] as $key => $value) {
            if (isset($value['key'])) {
                $key = $value['key'];
                unset($value['key']);
            }

            $valueParams = [
                'value'        => $key,
                'metrics'      => $this->getMetrics($value),
                'aggregations' => $this->getSubAggregations($value),
            ];

            $subAggregationsNames = $valueParams['aggregations']->getBucketNames();

            foreach (array_keys($valueParams['metrics']) as $metricName) {
                if (in_array($metricName, $subAggregationsNames)) {
                    unset($valueParams['metrics'][$metricName]);
                }
            }

            $values[] = $this->valueFactory->create($valueParams);
        }

        return $values;
    }

    /**
     * Parse a bucket and returns metrics.
     *
     * @param array $rawValue Bucket data.
     *
     * @return mixed
     */
    private function getMetrics($rawValue)
    {
        $metrics = [];

        foreach ($rawValue as $metricName => $value) {
            if (!is_array($value) || !isset($value['buckets'])) {
                $metricName = $metricName == 'doc_count' ? 'count' : $metricName;
                if (is_array($value) && isset($value['value_as_string'])) {
                    $value = $value['value_as_string'];
                } elseif (is_array($value) && isset($value['value'])) {
                    $value = $value['value'];
                }
                $metrics[$metricName] = $value;
            }
        }

        return $metrics;
    }

    /**
     * Parse a bucket and returns sub-aggregations.
     *
     * @param array $rawValue Bucket data.
     *
     * @return \Magento\Framework\Search\Response\Aggregation
     */
    private function getSubAggregations($rawValue)
    {
        $subAggregations = [];

        foreach ($rawValue as $key => $value) {
            if (is_array($value)) {
                while (is_array($value) && isset($value[$key]) && is_array($value[$key])) {
                    $value = $value[$key];
                }

                if (isset($value['buckets'])) {
                    $subAggregations[$key] = $value;
                }
            }
        }

        return $this->create($subAggregations);
    }
}
