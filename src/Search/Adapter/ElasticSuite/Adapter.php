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

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite;

use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Api\Search\Document;
use Magento\Framework\Search\Adapter\Mysql\DocumentFactory;
use Magento\Framework\Search\Response\Aggregation;
use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Response\QueryResponseFactory;
use Psr\Log\LoggerInterface;
use Smile\ElasticSuiteCore\Index\IndexOperation;
use Smile\ElasticSuiteCore\Api\Index\IndexInterface;
use Smile\ElasticSuiteCore\Api\Index\TypeInterface;
use Smile\ElasticSuiteCore\Api\Client\ClientFactoryInterface;
use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Query\Builder as QueryBuilder;


/**
 * ElasticSuite Search Adapter.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Adapter implements AdapterInterface
{
    private $responseFactory;
    private $logger;

    /**
     * @var \Smile\ElasticSuiteCore\Index\IndexOperation
     */
    private $indexManager;


    /**
     * @var \Elasticsearch\Client
     */
    private $client;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * Constructor.
     *
     *
     */
    public function __construct(
        QueryResponseFactory $responseFactory,
        LoggerInterface $logger,
        IndexOperation $indexManager,
        ClientFactoryInterface $clientFactory,
        QueryBuilder $queryBuilder
    ) {

        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
        $this->indexManager = $indexManager;
        $this->client = $clientFactory->createClient();
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function query(RequestInterface $request)
    {
        \Magento\Framework\Profiler::start('ES:' . __METHOD__, ['group' => 'ES', 'method' => __METHOD__]);

        try {
            $index = $this->getIndex($request);
            $type  = $index->getType($request->getType());
            $searchQuery = $this->buildSearchQuery($request);
            $searchQuery = $this->addPagination($request, $searchQuery);
            $searchResponse = $this->doSearch($index, $type, $searchQuery);
        } catch (\Exception $e) {
            $searchResponse = [];
            $this->logger->error($e->getMessage());
        }

        \Magento\Framework\Profiler::stop('ES:' . __METHOD__);

        return $this->responseFactory->create(['searchResponse' => $searchResponse]);
    }

    private function buildSearchQuery($request)
    {
        $query = [
            'query'  => $this->queryBuilder->buildQuery($request->getQuery()),
            'filter' => $this->queryBuilder->buildQuery($request->getFilter()),
        ];

        foreach ($request->getAggregation() as $currentAggregation) {
            $aggregationName = $currentAggregation->getName();
            $query['aggregations'][$aggregationName]['terms'] = [
                'field' => $currentAggregation->getField(),
            ];
        }

        //var_dump($query['aggregations']);

        return $query;
    }
    private function addPagination(RequestInterface $request, $searchQuery)
    {
        $searchQuery['size'] = $request->getSize();
        $searchQuery['from'] = $request->getFrom();

        return $searchQuery;
    }

    private function doSearch(IndexInterface $index, TypeInterface $type, $query)
    {
        $request = ['index' => $index->getName(), 'type' => $type->getName(), 'body' => $query];
        return $this->client->search($request);
    }

    private function getIndex(RequestInterface $request)
    {
        $indexIdentifier = $request->getIndex();
        $storeId = $request->getDimensions()['scope']->getValue();

        return $this->indexManager->getIndexByName($indexIdentifier, $storeId);
    }
}
