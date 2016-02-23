<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Request;

use Smile\ElasticSuiteCore\Search\Request\Config\Reader;
use Magento\Framework\Config\CacheInterface;
use Smile\ElasticSuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticSuiteCore\Api\Index\MappingInterface;
use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder\Bool;

/**
 * ElasticSuite Search requests configuration.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Config extends \Magento\Framework\Config\Data
{
    /**
     * Cache ID for Search Request
     *
     * @var string
     */
    const CACHE_ID = 'elasticsuite_request_declaration';

    /**
     * @var IndexSettingsInterface
     */
    private $indexSettings;

    /**
     * Constructor.
     *
     * @param Reader                 $reader        Config file reader.
     * @param CacheInterface         $cache         Cache interface.
     * @param IndexSettingsInterface $indexSettings Index settings.
     * @param string                 $cacheId       Config cache id.
     */
    public function __construct(
        Reader $reader,
        CacheInterface $cache,
        IndexSettingsInterface $indexSettings,
        $cacheId = self::CACHE_ID
    ) {
        parent::__construct($reader, $cache, $cacheId);
        $this->indexSettings = $indexSettings;
        $this->addTypesConfig();
    }

    /**
     * Append indices/types configuration to the search requests
     *
     * @return void
     */
    private function addTypesConfig()
    {
        $indicesConfig = $this->indexSettings->getIndicesConfig();

        foreach ($this->_data as $requestName => $data) {
            $index = $data['index'];
            $type  = $data['type'];

            if (isset($indicesConfig[$index]['types'][$type])) {
                $mapping = $indicesConfig[$index]['types'][$type]->getMapping();
                $this->_data[$requestName] = $this->addTypeConfig($mapping, $data);
            }
        }
    }

    /**
     * Load the configuration for an index / type couple and add the new field to the current configuration.
     *
     * @param MappingInterface $mapping Type mapping.
     * @param array            $data    Current configuration data
     *
     * @return array
     */
    private function addTypeConfig(MappingInterface $mapping, $data)
    {
        foreach ($mapping->getFields() as $mappingField) {
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

                if ($mappingField->isUsedForSortBy()) {
                    $sortOrder = $this->getSortOrderFromField($mappingField);
                    $data['sortOrders'][$sortOrder['name']] = $sortOrder;
                }
            }
        }

        return $this->addTypeFilters($filtersByType, $data);
    }

    /**
     * Retrieve filter query configuration for a field of the mapping.
     *
     * @param FieldInterface $field Field.
     *
     * @return array
     */
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

    /**
     * Retrieve aggregation configuration for a field of the mapping.
     *
     * @param FieldInterface $field Field.
     *
     * @return array
     */
    private function getAggregationsFromField(FieldInterface $field)
    {
        $fieldName       = $field->getName();
        $aggregationName = $fieldName . '_bucket';
        $aggregationType = BucketInterface::TYPE_TERM;

        $aggregation = [
            'name'  => $aggregationName,
            'field' => $fieldName,
            'type'  => $aggregationType,
        ];

        return $aggregation;
    }

    /**
     * Retrieve sort order configuration for a field of the mapping.
     *
     * @param FieldInterface $field Sortable field.
     *
     * @return array
     */
    private function getSortOrderFromField(FieldInterface $field)
    {
        $fieldName     = $field->getName() . '.' .FieldInterface::ANALYZER_SORTABLE;
        $sortOrderName = $field->getName();
        $direction     = '$sortOrder.direction$';
        $sortOrderType = SortOrderInterface::TYPE_STANDARD;

        return ['name' => $sortOrderName, 'field' => $fieldName, 'direction' => $direction, 'type' => $sortOrderType];
    }

    /**
     * Append new filters into the configuration.
     *
     * @param string $filtersByType Filter to be added by type (query or filter).
     * @param array  $data          Base data where filters have to be append.
     *
     * @return array
     */
    private function addTypeFilters($filtersByType, $data)
    {
        $defaultAddClause = Bool::QUERY_CONDITION_MUST;

        foreach ($filtersByType as $type => $filterQueries) {
            if (!isset($data[$type]) || empty($data[$type])) {
                $queryReferenceName = 'type_automatic_'. $type .'filtered';
                $data[$type] = ['reference' => $queryReferenceName];
            }

            $queryReferenceName = $data[$type]['reference'];

            if (!isset($data['queries'][$queryReferenceName])) {
                $data['queries'][$queryReferenceName] = [
                    'name' => $queryReferenceName,
                    'type' => QueryInterface::TYPE_BOOL,
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
}
