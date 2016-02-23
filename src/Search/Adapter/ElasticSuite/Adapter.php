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
use Smile\ElasticSuiteCore\Api\Client\ClientFactoryInterface;
use Smile\ElasticSuiteCore\Search\Request\Builder\Mapper;

/**
 * ElasticSuite Search Adapter.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
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
     * @var Request\Mapper
     */
    private $requestMapper;

    /**
     * Constructor.
     *
     * @param QueryResponseFactory   $responseFactory Search response factory.
     * @param IndexOperation         $indexManager    ES index manager.
     * @param Request\Mapper         $requestMapper   Search request mapper.
     * @param ClientFactoryInterface $clientFactory   ES Client Factory.
     * @param LoggerInterface        $logger          Logger.
     */
    public function __construct(
        QueryResponseFactory $responseFactory,
        IndexOperation $indexManager,
        Request\Mapper $requestMapper,
        ClientFactoryInterface $clientFactory,
        LoggerInterface $logger
    ) {
        $this->responseFactory = $responseFactory;
        $this->logger          = $logger;
        $this->indexManager    = $indexManager;
        $this->client          = $clientFactory->createClient();
        $this->requestMapper   = $requestMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function query(RequestInterface $request)
    {
        try {
            $searchResponse = $this->doSearch($request);
        } catch (\Exception $e) {
            $searchResponse = [];
            $this->logger->error($e->getMessage());
        }

        return $this->responseFactory->create(['searchResponse' => $searchResponse]);
    }

    /**
     * Execute the search request with ES.
     *
     * @param RequestInterface $request Search request.
     *
     * @return array
     */
    private function doSearch(RequestInterface $request)
    {
        $index = $this->getIndex($request);

        $searchRequest = [
            'index' => $index->getName(),
            'type'  => $request->getType(),
            'body'  => $this->requestMapper->buildSearchRequest($request),
        ];

        return $this->client->search($searchRequest);
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
