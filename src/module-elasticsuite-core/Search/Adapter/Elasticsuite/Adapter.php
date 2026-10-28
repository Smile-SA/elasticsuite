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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Api\Client\OpaqueIdProviderInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponseFactory;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;

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
     * @var ClientInterface
     */
    private $client;

    /**
     * @var Request\Mapper
     */
    private $requestMapper;

    /**
     * @var OpaqueIdProviderInterface
     */
    private $opaqueIdProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Constructor.
     *
     * @param QueryResponseFactory           $responseFactory  Search response factory.
     * @param Request\Mapper                 $requestMapper    Search request mapper.
     * @param ClientInterface                $client           ES Client Factory.
     * @param LoggerInterface                $logger           Logger.
     * @param OpaqueIdProviderInterface|null $opaqueIdProvider X-Opaque-Id provider.
     * @param StoreManagerInterface|null     $storeManager     Store manager.
     */
    public function __construct(
        QueryResponseFactory $responseFactory,
        Request\Mapper $requestMapper,
        ClientInterface $client,
        LoggerInterface $logger,
        ?OpaqueIdProviderInterface $opaqueIdProvider = null,
        ?StoreManagerInterface $storeManager = null
    ) {
        $this->responseFactory  = $responseFactory;
        $this->logger           = $logger;
        $this->client           = $client;
        $this->requestMapper    = $requestMapper;
        $this->opaqueIdProvider = $opaqueIdProvider ?? ObjectManager::getInstance()->get(OpaqueIdProviderInterface::class);
        $this->storeManager     = $storeManager ?? ObjectManager::getInstance()->get(StoreManagerInterface::class);
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
            'body'  => $this->requestMapper->buildSearchRequest($request),
        ];

        $details = ['cnt' => $request->getName()];
        // The store actually searched, rather than the current one (which differs in CLI or for the admin store).
        $storeCode = $this->getStoreCode($request);
        if ($storeCode !== null) {
            $details['str'] = $storeCode;
        }

        $opaqueId = $this->opaqueIdProvider->getOpaqueId($details);
        if ($opaqueId !== null) {
            $searchRequest['client'] = ['opaqueId' => $opaqueId];
        }

        return $this->client->search($searchRequest);
    }

    /**
     * Return the code of the store the search request applies to (its "scope" dimension), if known.
     *
     * @param RequestInterface $request Search request.
     *
     * @return string|null
     */
    private function getStoreCode(RequestInterface $request): ?string
    {
        $scope   = $request->getDimensions()['scope'] ?? null;
        $storeId = $scope ? $scope->getValue() : null;
        // No store, e.g. a search through the indices of all stores (StoreManager would return the current one).
        if ($storeId === null || $storeId === '') {
            return null;
        }

        try {
            return $this->storeManager->getStore($storeId)->getCode();
        } catch (\Exception $exception) {
            return null;
        }
    }
}
