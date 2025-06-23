<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteTracker\Model\Data\Fixer\Event;

use Magento\Framework\Search\SearchEngineInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Psr\Log\LogLevel;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Helper\IndexSettings;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\Aggregation\Value;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\Aggregation\Value as AggregationValue;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\Builder;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\RequestInterface;
use Smile\ElasticsuiteTracker\Api\EventIndexInterface;
use Smile\ElasticsuiteTracker\Api\SessionIndexInterface;
use Smile\ElasticsuiteTracker\Model\Data\Fixer\DataFixerInterface;
use Smile\ElasticsuiteTracker\Model\Data\Fixer\OutputAwareInterface;
use Smile\ElasticsuiteTracker\Model\Data\Fixer\ProgressIndicatorAwareInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Fix catalog_product_view product id when the value is 0.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class ViewedProductId implements DataFixerInterface, OutputAwareInterface, ProgressIndicatorAwareInterface
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var AggregationFactory
     */
    private $aggregationFactory;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var Builder
     */
    private $searchRequestBuilder;

    /**
     * @var SearchEngineInterface
     */
    private $searchEngine;

    /**
     * @var SessionIndexInterface
     */
    private $sessionIndex;

    /**
     * @var IndexSettings
     */
    private $indexSettings;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var array
     */
    private $productIdBySkus = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var boolean
     */
    private $loggingEnabled = false;

    /**
     * @var OutputInterface|null
     */
    private $output = null;

    /**
     * @var ProgressIndicator|null
     */
    private $progressIndicator = null;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param QueryFactory          $queryFactory         Query factory.
     * @param AggregationFactory    $aggregationFactory   Aggregation factory.
     * @param QueryBuilder          $queryBuilder         Query Builder.
     * @param ClientInterface       $client               Elasticsearch client.
     * @param Builder               $searchRequestBuilder Search request builder.
     * @param SearchEngineInterface $searchEngine         Search engine.
     * @param SessionIndexInterface $sessionIndex         Tracker sessions index.
     * @param IndexSettings         $indexSettings        Index settings helper.
     * @param ProductResource       $productResource      Catalog product resource model.
     * @param LoggerInterface       $logger               Logger.
     */
    public function __construct(
        QueryFactory $queryFactory,
        AggregationFactory $aggregationFactory,
        QueryBuilder $queryBuilder,
        ClientInterface $client,
        Builder $searchRequestBuilder,
        SearchEngineInterface $searchEngine,
        SessionIndexInterface $sessionIndex,
        IndexSettings $indexSettings,
        ProductResource $productResource,
        LoggerInterface $logger
    ) {
        $this->queryFactory = $queryFactory;
        $this->aggregationFactory = $aggregationFactory;
        $this->queryBuilder = $queryBuilder;
        $this->client = $client;
        $this->searchRequestBuilder = $searchRequestBuilder;
        $this->searchEngine = $searchEngine;
        $this->sessionIndex = $sessionIndex;
        $this->indexSettings = $indexSettings;
        $this->productResource = $productResource;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function fixInvalidData(int $storeId): int
    {
        try {
            $result = $this->fixViewedZeroProductId($storeId);
        } catch (\Exception $e) {
            $result = DataFixerInterface::FIX_FAILURE;
            $this->logger->error($e->getMessage());
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function setProgressIndicator(ProgressIndicator $progressIndicator): ProgressIndicatorAwareInterface
    {
        $this->progressIndicator = $progressIndicator;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getProgressIndicator(): ?ProgressIndicator
    {
        return $this->progressIndicator;
    }

    /**
     * {@inheritDoc}
     */
    public function hasProgressIndicator(): bool
    {
        return null !== $this->progressIndicator;
    }

    /**
     * {@inheritDoc}
     */
    public function setOutput(OutputInterface $output): OutputAwareInterface
    {
        $this->output = $output;

        if ($this->output->isVeryVerbose() || $this->output->isDebug()) {
            $this->loggingEnabled = true;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOutput(): ?OutputInterface
    {
        return $this->output;
    }

    /**
     * {@inheritDoc}
     */
    public function hasOutput(): bool
    {
        return null !== $this->output;
    }

    /**
     * Lookup a product id from its sku from the cache or DB.
     * Returns false if the product could not be found.
     *
     * @param string $sku Product SKU
     *
     * @return int|false
     */
    private function getProductIdBySku($sku)
    {
        if (!array_key_exists($sku, $this->productIdBySkus)) {
            $this->productIdBySkus[$sku] = $this->productResource->getIdBySku($sku);
        }

        return $this->productIdBySkus[$sku];
    }

    /**
     * Fix product view events with a product id of '0' and their corresponding sessions.
     *
     * @param int $storeId Store Id.
     *
     * @return int
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function fixViewedZeroProductId($storeId): int
    {
        $result = DataFixerInterface::FIX_COMPLETE;
        $missingSkus = [];

        $localProgressBar = $this->getProgressBar($storeId);
        foreach ($this->getInvalidEventsSkuToProductId($storeId, $missingSkus) as $sku => $productId) {
            $this->log(LogLevel::INFO, sprintf('[TrackerFixData] Will replace product id "0" by id "%d" for "%s"', $productId, $sku));
            if ($this->hasProgressIndicator()) {
                $this->getProgressIndicator()->advance();
            }
            if ($localProgressBar) {
                $localProgressBar->advance();
            }
            foreach ($this->getInvalidEventsSessionsForSku($storeId, $sku) as $sessionsChunk) {
                $this->log(LogLevel::INFO, sprintf("[TrackerFixData] - Applying changes on %d sessions", count($sessionsChunk)));
                $this->replaceViewedZeroProductIdBy($storeId, $productId, $sku, $sessionsChunk);
            }
        }

        $this->cleanupDeletedSkus($storeId, $missingSkus);

        if ($localProgressBar) {
            $localProgressBar->finish();
        }
        if ($this->hasProgressIndicator()) {
            $this->getProgressIndicator()->advance();
        }

        return $result;
    }

    /**
     * Initializes and returns a local progress bar.
     *
     * @param int $storeId Store ID.
     *
     * @return ProgressBar|null
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function getProgressBar($storeId): ?ProgressBar
    {
        $progressBar = null;

        if ($this->hasOutput()) {
            if ($this->hasProgressIndicator()) {
                $this->getOutput()->setDecorated(true);
            }
            ProgressBar::setFormatDefinition('custom', " %current%/%max% skus...%message% ");
            $outputSection = $this->getOutput()->section();
            $progressBar = new ProgressBar($outputSection, $this->getInvalidEventsSkusCount($storeId), 1.5);
            $progressBar->setFormat('custom');
            $progressBar->setMessage(
                sprintf('Fixing product views with product id "0" for store %d', $storeId)
            );
            $progressBar->start();
        }

        return $progressBar;
    }

    /**
     * Log a message if logging is enabled.
     *
     * @param int    $level   Log level of the messagE.
     * @param string $message Message.
     * @param array  $context Optional additional context.
     *
     * @return void
     */
    private function log($level, $message, array $context = [])
    {
        if ($this->loggingEnabled) {
            $this->logger->log($level, $message, $context);
        }
    }

    /**
     * Replaces the product id '0' by the provided id for the specific sku in the events of the matching sessions.
     * And then refresh those session.
     *
     * @param int    $storeId   Store Id.
     * @param int    $productId The product ID to replace '0' by.
     * @param string $sku       The product SKU the replacement is for.
     * @param array  $sessions  List of session ids whose product view events should be fixed.
     */
    private function replaceViewedZeroProductIdBy($storeId, $productId, $sku, $sessions)
    {
        // Fixes events.
        $query = $this->queryFactory->create(
            QueryInterface::TYPE_BOOL,
            [
                'must' => [
                    $this->getInvalidEventsQuery(),
                    $this->queryFactory->create(
                        QueryInterface::TYPE_TERM,
                        ['field' => 'page.product.sku.untouched', 'value' => $sku]
                    ),
                    $this->queryFactory->create(
                        QueryInterface::TYPE_TERMS,
                        ['field' => 'session.uid', 'values' => array_values($sessions)]
                    ),
                ],
            ]
        );
        $params = [
            'index' => $this->indexSettings->getIndexAliasFromIdentifier(EventIndexInterface::INDEX_IDENTIFIER, $storeId),
            /* 'type' => '_doc', */
            'body' => [
                'script' => [
                    'source' => "ctx._source.remove('page.product.id'); ctx._source.page.product.id = Integer.parseInt(params.productId)",
                    'lang' => 'painless',
                    'params' => [
                        'productId' => $productId,
                    ],
                ],
                'query' => $this->queryBuilder->buildQuery($query),
            ],
            'conflicts' => 'proceed',
            'wait_for_completion' => true,
        ];
        $this->client->updateByQuery($params);


        // Alter sessions' product_view by removing '0' if present, adding the correct product id if missing.
        $query = $this->queryFactory->create(
            QueryInterface::TYPE_BOOL,
            [
                'must' => [
                    $this->queryFactory->create(
                        QueryInterface::TYPE_TERMS,
                        ['field' => 'session_id', 'values' => array_values($sessions)]
                    ),
                ],
                'should' => [
                    $this->queryFactory->create(
                        QueryInterface::TYPE_TERM,
                        ['field' => 'product_view', 'value' => 0]
                    ),
                    $this->queryFactory->create(
                        QueryInterface::TYPE_NOT,
                        [
                            'query' => $this->queryFactory->create(
                                QueryInterface::TYPE_TERM,
                                ['field' => 'product_view', 'value' => $productId]
                            ),
                        ]
                    ),
                ],
            ]
        );
        $script  = ' if (!ctx._source.containsKey(\'product_view\')) { ctx._source.product_view = Integer.parseInt(params.productId) } ';
        $script .= ' else {';
        $script .= '   if (ctx._source.product_view.contains(0)) { ctx._source.product_view.remove(ctx._source.product_view.indexOf(0)) }';
        $script .= '   if (!ctx._source.product_view.contains(Integer.parseInt(params.productId))) {';
        $script .= '      ctx._source.product_view.add(Integer.parseInt(params.productId))';
        $script .= '   }';
        $script .= ' }';
        $params = [
            'index' => $this->indexSettings->getIndexAliasFromIdentifier(SessionIndexInterface::INDEX_IDENTIFIER, $storeId),
            /* 'type' => '_doc', */
            'body' => [
                'script' => [
                    'source' => $script,
                    'lang' => 'painless',
                    'params' => [
                        'productId' => $productId,
                    ],
                ],
                'query' => $this->queryBuilder->buildQuery($query),
            ],
            'conflicts' => 'proceed',
            'wait_for_completion' => true,
        ];
        $this->client->updateByQuery($params);
    }

    /**
     * Remove product views events related to skus.
     *
     * @param int   $storeId     Store Id.
     * @param array $deletedSkus Array of deleted skus.
     *
     * @return void
     */
    private function cleanupDeletedSkus($storeId, $deletedSkus)
    {
        // Remove non fixable product views events due to products/sku deleted from the DB.
        if (!empty($deletedSkus)) {
            sort($deletedSkus, SORT_NATURAL | SORT_FLAG_CASE);
            $this->log(LogLevel::INFO, "[TrackerFixData] Deleting events for skus no longer corresponding to a product");

            $deletedSkusChunks = array_chunk($deletedSkus, 100);
            foreach ($deletedSkusChunks as $chunk) {
                $this->log(LogLevel::INFO, sprintf("[TrackerFixData] - Missing skus: %s", implode(', ', $deletedSkus)));
                $this->log(LogLevel::INFO, sprintf("[TrackerFixData] - Deleting non fixable product views for %d skus", count($chunk)));
                $query = $this->queryFactory->create(
                    QueryInterface::TYPE_BOOL,
                    [
                        'must' => [
                            $this->getInvalidEventsQuery(),
                            $this->queryFactory->create(
                                QueryInterface::TYPE_TERMS,
                                ['field' => 'page.product.sku.untouched', 'values' => array_values($chunk)]
                            ),
                        ],
                    ]
                );
                $params = [
                    'index' => $this->indexSettings->getIndexAliasFromIdentifier(EventIndexInterface::INDEX_IDENTIFIER, $storeId),
                    'body' => ['query' => $this->queryBuilder->buildQuery($query)],
                    'conflicts' => 'proceed',
                    'wait_for_completion' => true,
                ];
                $this->client->deleteByQuery($params);
            }
        }
    }

    /**
     * Returns the query corresponding to invalid but fixable product view events.
     *
     * @return QueryInterface
     */
    private function getInvalidEventsQuery()
    {
        return $this->queryFactory->create(
            QueryInterface::TYPE_BOOL,
            [
                'must' => [
                    $this->queryFactory->create(
                        QueryInterface::TYPE_TERM,
                        ['field' => 'page.type.identifier', 'value' => 'catalog_product_view']
                    ),
                    $this->queryFactory->create(
                        QueryInterface::TYPE_TERM,
                        ['field' => 'page.product.id', 'value' => 0]
                    ),
                    $this->queryFactory->create(
                        QueryInterface::TYPE_EXISTS,
                        ['field' => 'page.product.sku']
                    ),
                ],
            ]
        );
    }

    /**
     * Return the number of unique skus for invalid product view events for the provided store id.
     *
     * @param int $storeId Store ID.
     *
     * @return int
     */
    private function getInvalidEventsSkusCount($storeId)
    {
        $count = 0;

        $queryFilters = [
            $this->getInvalidEventsQuery(),
        ];
        $skuAgg = $this->aggregationFactory->create(
            BucketInterface::TYPE_METRIC,
            [
                'name'  => 'count',
                'field' => 'page.product.sku.untouched',
                'metricType' => MetricInterface::TYPE_CARDINALITY,
            ]
        );

        $request = $this->searchRequestBuilder->create(
            $storeId,
            EventIndexInterface::INDEX_IDENTIFIER,
            0,
            0,
            null,
            [],
            [],
            $queryFilters,
            [$skuAgg]
        );
        $response = $this->searchEngine->search($request);
        if ($response->getAggregations()->getBucket('count')) {
            /** @var Value $countMetric */
            $countMetric = current($response->getAggregations()->getBucket('count')->getValues());
            $count = (int) $countMetric->getMetrics()['value'] ?? 0;
        }

        return $count;
    }

    /**
     * Get mapping sku -> productId for invalid product view events.
     *
     * @param int   $storeId      Store Id.
     * @param array $excludedSkus Skus to exclude.
     *
     * @return \Traversable
     */
    private function getInvalidEventsSkuToProductId($storeId, &$excludedSkus)
    {
        /*
         * The do...while works to paginate (and is not infinite) as long as between the request executions both
         * - the invalid events for a given sku are already fixed ;
         * - and the non-fixable events' skus are ignored.
         * This means the updateByQuery fixing calls cannot be "wait_for_completion = false".
         */
        do {
            $hasMoreSkus = false;
            $skuRequest = $this->getInvalidEventsSkuRequest($storeId, $excludedSkus);
            $skuResponse = $this->searchEngine->search($skuRequest);
            if ($skuResponse->getAggregations()->getBucket('sku')) {
                $skuItems = $skuResponse->getAggregations()->getBucket('sku')->getValues();

                foreach ($skuItems as $skuItem) {
                    /** @var $skuItem AggregationValue */
                    $sku = $skuItem->getValue();
                    if ($sku === '__other_docs') {
                        $hasMoreSkus = true;
                        continue;
                    }
                    $productId = $this->getProductIdBySku($sku);
                    if (!$productId) {
                        $excludedSkus[] = $sku;
                        continue;
                    }

                    yield $sku => $productId;
                }
            }
        } while ($hasMoreSkus);
    }

    /**
     * Get session Ids of invalid events where the product Id will be fixed for the provided sku.
     *
     * @param int    $storeId Store Id.
     * @param string $sku     SKU to get invalid events for.
     *
     * @return \Traversable
     */
    private function getInvalidEventsSessionsForSku($storeId, $sku)
    {
        /*
         * The do...while works to paginate (and is not infinite) as long as the invalid events for the given sku
         * and the previously reported sessions are already fixed.
         * This means the updateByQuery fixing calls cannot be "wait_for_completion = false".
         */
        do {
            $hasMoreSessions = false;
            $sessions = [];

            $sessionRequest = $this->getInvalidSessionsForSkuRequest($storeId, $sku);
            $sessionResponse = $this->searchEngine->search($sessionRequest);
            if ($sessionResponse->getAggregations()->getBucket('sessionId')) {
                $sessionIdItems = $sessionResponse->getAggregations()->getBucket('sessionId')->getValues();
                foreach ($sessionIdItems as $sessionIdItem) {
                    /** @var $sessionIdItem AggregationValue */
                    $sessionId = $sessionIdItem->getValue();
                    if ($sessionId === '__other_docs') {
                        $hasMoreSessions = true;
                        continue;
                    }
                    if ($sessionId === 'null') {
                        continue;
                    }
                    $sessions[] = $sessionId;
                }

                if (!empty($sessions)) {
                    $chunks = array_chunk($sessions, 100);
                    foreach ($chunks as $chunk) {
                        yield $chunk;
                    }
                }
            }
        } while ($hasMoreSessions);
    }

    /**
     * Build a search request used to collect SKUs of invalid product view events with a product id of 0.
     * OR NOT Also collects the corresponding sessions of such invalid events.
     *
     * @param int   $storeId      Store id.
     * @param array $excludedSkus SKUs to exclude.
     *
     * @return RequestInterface
     */
    private function getInvalidEventsSkuRequest($storeId, $excludedSkus = [])
    {
        $queryFilters = [
            $this->getInvalidEventsQuery(),
        ];
        if (!empty($excludedSkus)) {
            $queryFilters[] = $this->queryFactory->create(
                QueryInterface::TYPE_NOT,
                [
                    'query' => $this->queryFactory->create(
                        QueryInterface::TYPE_TERMS,
                        ['field' => 'page.product.sku.untouched', 'values' => array_values($excludedSkus)]
                    ),
                ]
            );
        }

        $skuAgg = $this->aggregationFactory->create(
            BucketInterface::TYPE_TERM,
            [
                'name'  => 'sku',
                'field' => 'page.product.sku.untouched',
                'size'  => 250,
            ]
        );

        return $this->searchRequestBuilder->create(
            $storeId,
            EventIndexInterface::INDEX_IDENTIFIER,
            0,
            0,
            null,
            [],
            [],
            $queryFilters,
            [$skuAgg]
        );
    }

    /**
     * Build a search request used to collect session IDs for invalid product view events of a given sku.
     *
     * @param int    $storeId Store id.
     * @param string $sku     A SKU for which there is invalid product view events.
     *
     * @return RequestInterface
     */
    private function getInvalidSessionsForSkuRequest($storeId, $sku)
    {
        $queryFilters = [
            $this->getInvalidEventsQuery(),
            $this->queryFactory->create(
                QueryInterface::TYPE_TERM,
                ['field' => 'page.product.sku.untouched', 'value' => $sku]
            ),
        ];

        $sessionIdAgg = $this->aggregationFactory->create(
            BucketInterface::TYPE_TERM,
            [
                'name'  => 'sessionId',
                'field' => 'session.uid',
                'size'  => 250,
            ]
        );

        return $this->searchRequestBuilder->create(
            $storeId,
            EventIndexInterface::INDEX_IDENTIFIER,
            0,
            0,
            null,
            [],
            [],
            $queryFilters,
            [$sessionIdAgg]
        );
    }
}
