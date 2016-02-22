<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Request;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Search\Request\Binder;
use Smile\ElasticSuiteCore\Search\Request\Config;
use Smile\ElasticSuiteCore\Search\Request\Builder\Cleaner;
use Magento\Framework\Search\SearchEngineInterface;
use Smile\ElasticSuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticSuiteCore\Api\Index\MappingInterface;
use Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Query\Builder\BoolExpression;
use Magento\Framework\Search\Response\Bucket;
use Magento\Framework\Search\Request\BucketInterface;

class Builder
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var IndexSettingsInterface
     */
    private $indexSettings;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Binder
     */
    private $binder;

    /**
     * @var array
     */
    private $data = [
        'dimensions' => [],
        'placeholder' => [],
    ];

    /**
     * @var Cleaner
     */
    private $cleaner;

    /**
     * Request Builder constructor
     *
     * @param ObjectManagerInterface $objectManager
     * @param Config $config
     * @param Binder $binder
     * @param Cleaner $cleaner
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Config $config,
        IndexSettingsInterface $indexSettings,
        Binder $binder,
        Cleaner $cleaner
    ) {
        $this->objectManager = $objectManager;
        $this->config = $config;
        $this->indexSettings = $indexSettings;
        $this->binder = $binder;
        $this->cleaner = $cleaner;
    }


    /**
     * Set request name
     *
     * @param string $requestName
     * @return $this
     */
    public function setRequestName($requestName)
    {
        $this->data['requestName'] = $requestName;
        return $this;
    }

    /**
     * Set size
     *
     * @param int $size
     * @return $this
     */
    public function setSize($size)
    {
        $this->data['size'] = $size;
        return $this;
    }

    /**
     * Set from
     *
     * @param int $from
     * @return $this
     */
    public function setFrom($from)
    {
        $this->data['from'] = $from;
        return $this;
    }

    /**
     * Bind dimension data by name
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function bindDimension($name, $value)
    {
        $this->data['dimensions'][$name] = $value;
        return $this;
    }

    /**
     * Bind data to placeholder
     *
     * @param string $placeholder
     * @param mixed $value
     * @return $this
     */
    public function bind($placeholder, $value)
    {
        $this->data['placeholder']['$' . $placeholder . '$'] = $value;
        return $this;
    }

    /**
     * Create request object
     *
     * @return RequestInterface
     */
    public function create()
    {
        \Magento\Framework\Profiler::start('ES:' . __METHOD__, ['group' => 'ES', 'method' => __METHOD__]);
        if (!isset($this->data['requestName'])) {
            throw new \InvalidArgumentException("Request name not defined.");
        }
        $requestName = $this->data['requestName'];

        /** @var array $data */
        $data = $this->getConfig($requestName);

        // Binder hopes to find a filters field into the array.
        // We put this even if it does not make sense for our builder.
        $data['filters'] = [];

        $data = $this->binder->bind($data, $this->data);
        $data = $this->cleaner->clean($data);
        $data = $this->convert($data);

        \Magento\Framework\Profiler::stop('ES:' . __METHOD__);

        return $data;
    }

    protected function getConfig($requestName)
    {
        $data = $this->config->get($requestName);

        if ($data === null) {
            throw new \InvalidArgumentException("Request name '{$requestName}' doesn't exist.");
        }

        $data = $this->addTypeConfig($data['index'], $data['type'], $data);

        return $data;
    }

    private function addTypeConfig($index, $type, $data)
    {
        $indexConfig = $this->indexSettings->getIndicesConfig();

        if (isset($indexConfig[$index]) && isset($indexConfig[$index]['types'][$type])) {

            /**
             * @var MappingInterface
             */
            $mapping = $indexConfig[$index]['types'][$type]->getMapping();

            $filtersByType = [];

            foreach ($mapping->getFields() as $mappingField)
            {
                if ($mappingField->isFilterable()) {

                    $filterQuery = $this->getFilterQueryFromField($mappingField);
                    $filterQueryName = $filterQuery['name'];

                    $data['queries'][$filterQueryName] = $filterQuery;

                    $filterType = 'query';

                    if ($mappingField->isFacet($data['name'])) {
                        $filterType = 'filter';
                        $aggregation = $this->getAggregationsFromField($mappingField);
                        $data['aggregations'][$aggregation['name']] = $aggregation;
                    }

                    $filtersByType[$filterType][] = $filterQuery;
                }
            }

            $data = $this->addTypeFilters($filtersByType, $data);
        }

        return $data;
    }

    private function getFilterQueryFromField(FieldInterface $field)
    {
        $fieldName       = $field->getName();
        $filterName      = $fieldName . '_filter';
        $filterType      = QueryInterface::TYPE_TERMS;
        $filterBindField = 'values';
        $filterBindValue = sprintf('$%s$', $fieldName);

        $filterQuery = [
            'name'           => $filterName,
            'field'          => $fieldName,
            'type'           => $filterType,
            $filterBindField => $filterBindValue,
        ];

        return $filterQuery;
    }

    private function getAggregationsFromField(FieldInterface $field)
    {
        $fieldName       = $field->getName();
        $aggregationName = $fieldName . '_bucket';
        $aggregationType = BucketInterface::TYPE_TERM;

        $aggregation = [
            'name'  => $aggregationName,
            'field' => $fieldName,
            'type'  => $aggregationType
        ];

        return $aggregation;
    }

    private function addTypeFilters($filtersByType, $data)
    {
        $defaultAddClause = BoolExpression::QUERY_CONDITION_MUST;

        foreach ($filtersByType as $type => $filterQueries) {

            if (!isset($data[$type])) {
                $queryReferenceName = 'type_automatic_'. $type .'filtered';
            }

            $queryReferenceName = $data[$type]['reference'];

            if (!isset($data['queries'][$queryReferenceName])) {
                $data['queries'][$queryReferenceName] = [
                    'name' => $queryReferenceName,
                    'type' => QueryInterface::TYPE_BOOL
                ];
            } elseif ($data['queries'][$queryReferenceName]['type'] != QueryInterface::TYPE_BOOL) {
                $oldReferenceName   = $queryReferenceName;
                $queryReferenceName = 'type_automatic_'. $type .'filtered';
                $data['queries'][$queryReferenceName] = [
                    'name' => $queryReferenceName,
                    'type' => QueryInterface::TYPE_BOOL,
                    'query' => [['clause' => $defaultAddClause, 'reference' => $oldReferenceName]],
                ];
            }

            foreach ($filterQueries as $filterQuery) {
                $data['queries'][$queryReferenceName]['query'][] = [
                    'clause'    => $defaultAddClause,
                    'reference' => $filterQuery['name'],
                ];
            }

            $data[$type] = ['reference' => $queryReferenceName];
        }

        return $data;
    }

    /**
     * Convert array to Request instance
     *
     * @param array $data
     * @return RequestInterface
     */
    private function convert($data)
    {
        $mapperClass = 'Smile\ElasticSuiteCore\Search\Request\Builder\Mapper';
        /** @var Mapper $mapper */
        $mapper = $this->objectManager->create($mapperClass, ['requestData' => $data]);

        $searchRequestParams = [
            'name'       => $data['name'],
            'indexName'  => $data['index'],
            'type'       => $data['type'],
            'from'       => $data['from'],
            'size'       => $data['size'],
            'query'      => $mapper->getRootQuery(),
            'filter'     => $mapper->getRootFilter(),
            'buckets'    => $mapper->getAggregations(),
            'dimensions' => $this->buildDimensions(isset($data['dimensions']) ? $data['dimensions'] : []),
        ];

        return $this->objectManager->create('Smile\ElasticSuiteCore\Search\Request', $searchRequestParams);
    }

    /**
     * @param array $dimensionsData
     * @return array
     */
    private function buildDimensions(array $dimensionsData)
    {
        $dimensions = [];
        foreach ($dimensionsData as $dimensionData) {
            $dimensions[$dimensionData['name']] = $this->objectManager->create(
                'Magento\Framework\Search\Request\Dimension',
                $dimensionData
                );
        }
        return $dimensions;
    }

}