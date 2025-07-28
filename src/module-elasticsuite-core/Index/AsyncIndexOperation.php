<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Index;

use Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Index\AsyncIndexOperationInterface;

/**
 * Asynchronous Index Operations interface
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AsyncIndexOperation extends IndexOperation implements AsyncIndexOperationInterface
{
    /**
     * @var array
     */
    private $futureBulks = [];

    /**
     * @var \Smile\ElasticsuiteCore\Api\Client\ClientInterface
     */
    private $client;

    /**
     * @var integer
     */
    private $parallelHandles;

    /**
     * Instantiate the index operation manager.
     *
     * @param \Magento\Framework\ObjectManagerInterface                       $objectManager       Object manager.
     * @param \Smile\ElasticsuiteCore\Api\Client\ClientInterface              $client              ES client.
     * @param \Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface $clientConfiguration ES client configuration.
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface        $indexSettings       ES settings.
     * @param \Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface        $clusterInfo         ES cluster information.
     * @param \Smile\ElasticsuiteCore\Model\Index\BulkError\Manager           $bulkErrorManager    Bulk error manager.
     * @param \Psr\Log\LoggerInterface                                        $logger              Logger access.
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Smile\ElasticsuiteCore\Api\Client\ClientInterface $client,
        \Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface $clientConfiguration,
        \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface $indexSettings,
        \Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface $clusterInfo,
        \Smile\ElasticsuiteCore\Model\Index\BulkError\Manager $bulkErrorManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->client          = $client;
        $this->parallelHandles = $clientConfiguration->getMaxParallelHandles();
        parent::__construct($objectManager, $client, $indexSettings, $clusterInfo, $bulkErrorManager, $logger);
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    public function executeBulk(\Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface $bulk)
    {
        $this->addFutureBulk($bulk);

        // Authoritative resolution of futures when reaching the threshold.
        // We do not let the Elasticsearch client resolve by itself because we want to deal with the response.
        if ($this->shouldResolveFutures()) {
            $this->resolveFutureBulks();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function addFutureBulk(\Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface $bulk)
    {
        if ($bulk->isEmpty()) {
            throw new \LogicException('Can not execute empty bulk.');
        }

        $bulkParams = ['body' => $bulk->getOperations()];

        // Use future mode of the client.
        // Will stack all bulk operations and execute them later with only one curl_multi_exec call in parallel mode.
        $bulkParams['client'] = ['future' => 'lazy'];

        // This is not executed in real time but put into a future bulk queue.
        $this->futureBulks[] = $this->client->bulk($bulkParams);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveFutureBulks()
    {
        $result = [];

        // Iterating on future response
        // and accessing properties like 'items' or 'error' will cause the queue to process.
        // It's like manually resolving by calling $this->futureBulks[sizeof($this->futureBulks) - 1]->wait(); .

        /** @var \GuzzleHttp\Ring\Future\FutureArray $futureBulkResponse */
        foreach ($this->futureBulks as $futureBulkResponse) {
            $resolvedResponse = [
                'items'  => $futureBulkResponse['items'],  // Implicit resolution of the promise.
                'errors' => $futureBulkResponse['errors'], // Implicit resolution of the promise.
            ];

            $result[] = $this->parseBulkResponse($resolvedResponse);
        }

        $this->futureBulks = [];

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function refreshIndex(\Smile\ElasticsuiteCore\Api\Index\IndexInterface $index)
    {
        $this->resolveFutureBulks();

        return parent::refreshIndex($index);
    }

    /**
     * Resolve future bulks on object destruction.
     * This would also be done by the Elasticsearch client through the Guzzle MultiCurl handler,
     * but would prevent having any bulk errors shown in the logs.
     */
    public function __destruct()
    {
        $this->resolveFutureBulks();
    }

    /**
     * Check if there is enough futures operations to be resolved.
     *
     * @return bool
     */
    private function shouldResolveFutures()
    {
        return (count($this->futureBulks) >= $this->parallelHandles);
    }
}
