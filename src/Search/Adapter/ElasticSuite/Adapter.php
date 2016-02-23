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

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite;

use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Response\QueryResponseFactory;
use Psr\Log\LoggerInterface;
use Smile\ElasticSuiteCore\Index\IndexOperation;
use Smile\ElasticSuiteCore\Api\Index\IndexInterface;
use Smile\ElasticSuiteCore\Api\Index\TypeInterface;
use Smile\ElasticSuiteCore\Api\Client\ClientFactoryInterface;
use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\SortOrder\Builder as SortOrderBuilder;

/**
 * ElasticSuite Search Adapter.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Adapter implements AdapterInterface
{
    /**
     * @var QueryResponseFactory
     */
    private $responseFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var IndexOperation
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
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * Constructor.
     *
     * @param QueryResponseFactory   $responseFactory  Search response factory.
     * @param IndexOperation         $indexManager     ES index manager.
     * @param QueryBuilder           $queryBuilder     Adapter query builder
     * @param SortOrderBuilder       $sortOrderBuilder Adapter sort orders builder
     * @param ClientFactoryInterface $clientFactory    ES client factory.
     * @param LoggerInterface        $logger           Logger
     */
    public function __construct(
        QueryResponseFactory $responseFactory,
        IndexOperation $indexManager,
        QueryBuilder $queryBuilder,
        SortOrderBuilder $sortOrderBuilder,
        ClientFactoryInterface $clientFactory,
        LoggerInterface $logger
    ) {
        $this->responseFactory = $responseFactory;
        $this->logger           = $logger;
        $this->indexManager     = $indexManager;
        $this->client           = $clientFactory->createClient();
        $this->queryBuilder     = $queryBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function query(RequestInterface $request)
    {
        try {
            $index = $this->getIndex($request);
            $type  = $index->getType($request->getType());
            $searchQuery = $this->buildSearchQuery($request);
            $searchResponse = $this->doSearch($index, $type, $searchQuery);
        } catch (\Exception $e) {
            $searchResponse = [];
            $this->logger->error($e->getMessage());
        }

        return $this->responseFactory->create(['searchResponse' => $searchResponse]);
    }

    /**
     * Build an ES search query from RequestInterface request.
     *
     * @param RequestInterface $request Search request to be mapped.
     *
     * @return array
     */
    private function buildSearchQuery(RequestInterface $request)
    {
        $query = [
            'query'  => $this->queryBuilder->buildQuery($request->getQuery()),
            'filter' => $this->queryBuilder->buildQuery($request->getFilter()),
            'sort'   => $this->sortOrderBuilder->buildSortOrders($request->getSortOrders()),
            'from'   => $request->getFrom(),
            'size'   => $request->getSize(),
        ];

        foreach ($request->getAggregation() as $currentAggregation) {
            $aggregationName = $currentAggregation->getName();
            $query['aggregations'][$aggregationName]['terms'] = [
                'field' => $currentAggregation->getField(),
            ];
        }

        return $query;
    }

    /**
     * Execute the search with ES.
     *
     * @param IndexInterface $index Index.
     * @param TypeInterface  $type  Document type.
     * @param array          $query Search query.
     *
     * @return array
     */
    private function doSearch(IndexInterface $index, TypeInterface $type, array $query)
    {
        $request = [
            'index' => $index->getName(),
            'type'  => $type->getName(),
            'body'  => $query,
        ];

        return $this->client->search($request);
    }

    /**
     * Retrive the index for the current request.
     *
     * @param RequestInterface $request Request.
     *
     * @return IndexInterface
     */
    private function getIndex(RequestInterface $request)
    {
        $indexIdentifier = $request->getIndex();
        $storeId = $request->getDimensions()['scope']->getValue();

        return $this->indexManager->getIndexByName($indexIdentifier, $storeId);
    }
}
