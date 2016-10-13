<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite;

use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponseFactory;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientFactoryInterface;

/**
 * ElasticSuite Search Adapter.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
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
     * @param Request\Mapper         $requestMapper   Search request mapper.
     * @param ClientFactoryInterface $clientFactory   ES Client Factory.
     * @param LoggerInterface        $logger          Logger.
     */
    public function __construct(
        QueryResponseFactory $responseFactory,
        Request\Mapper $requestMapper,
        ClientFactoryInterface $clientFactory,
        LoggerInterface $logger
    ) {
        $this->responseFactory = $responseFactory;
        $this->logger          = $logger;
        $this->client          = $clientFactory->createClient();
        $this->requestMapper   = $requestMapper;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * {@inheritdoc}
     */
    public function query(RequestInterface $request)
    {
        \Magento\Framework\Profiler::start('ES:Execute Search Query');

        try {
            $searchResponse = $this->doSearch($request);
        } catch (\Exception $e) {
            $searchResponse = [];
            $this->logger->error($e->getMessage());
        }

        \Magento\Framework\Profiler::stop('ES:Execute Search Query');

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
        $searchRequest = [
            'index' => $request->getIndex(),
            'type'  => $request->getType(),
            'body'  => $this->requestMapper->buildSearchRequest($request),
        ];

        return $this->client->search($searchRequest);
    }
}
