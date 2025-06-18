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
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Helper\IndexSettings;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\Aggregation\Value as AggregationValue;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\Builder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;
use Smile\ElasticsuiteCore\Search\RequestInterface;
use Smile\ElasticsuiteTracker\Api\EventIndexInterface;
use Smile\ElasticsuiteTracker\Api\SessionIndexInterface;
use Smile\ElasticsuiteTracker\Model\Data\Fixer\DataFixerInterface;

/**
 * Fix catalog_product_view product id when the value is 0.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class ViewedProductId implements DataFixerInterface
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
        }

        return $result;
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
        $hasMoreSkus = false;
        $hasMoreSessions = false;
        $missingSkus = [];

        $skuRequest = $this->getInvalidEventsSkuRequest($storeId);
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
                    $missingSkus[] = $sku;
                    continue;
                }

                $this->logger->info(
                    sprintf(
                        '[TrackerFixData] Will replace product id "0" by id "%d" for "%s"',
                        $productId,
                        $sku
                    )
                );
                $sessionsForSku = [];

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
                        $sessionsForSku[] = $sessionId;
                    }

                    $sessionChunks = array_chunk($sessionsForSku, 100);
                    foreach ($sessionChunks as $chunk) {
                        $this->logger->info(sprintf("[TrackerFixData] - Applying changes on %d sessions", count($chunk)));
                        $this->replaceViewedZeroProductIdBy($storeId, $productId, $sku, $chunk);
                    }
                }
            }
        }

        if (!empty($missingSkus)) {
            sort($missingSkus, SORT_NATURAL | SORT_FLAG_CASE);
            $this->logger->info("[TrackerFixData] Deleting events for skus no longer corresponding to a product");
            $this->logger->info(
                sprintf(
                    "[TrackerFixData] Missing skus: %s",
                    implode(', ', $missingSkus)
                )
            );
            $this->cleanupDeletedSkus($storeId, $missingSkus);
        }

        if ($hasMoreSkus || $hasMoreSessions) {
            $result = DataFixerInterface::FIX_PARTIAL;
            if ($hasMoreSkus) {
                $this->logger->info("[TrackerFixData] There are also more product id '0' for other skus");
            }
            if ($hasMoreSessions) {
                $this->logger->info('[TrackerFixData] There are also more events/sessions to fix for fixed skus');
            }
        }

        return $result;
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
        $this->logger->info("[TrackerFixData]   - Update by query of tracker events (replacing the product id 0)");
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
                    'source' => "ctx._source.remove('page.product.id'); ctx._source.page.product.id = params.productId",
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
        $this->logger->info(
            sprintf(
                "[TrackerFixData]   - Update by query of tracker sessions (removal of product id 0, adding %d if necessary)",
                $productId
            )
        );
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
        $script = 'if (ctx._source.product_view.contains(0)) { ctx._source.product_view.remove(ctx._source.product_view.indexOf(0)) }';
        $script .= ' if (!ctx._source.product_view.contains(params.productId)) { ctx._source.product_view.add(params.productId) }';
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
            $deletedSkusChunks = array_chunk($deletedSkus, 100);
            foreach ($deletedSkusChunks as $chunk) {
                $this->logger->info(sprintf("[TrackerFixData] - Deleting non fixable product views for %d skus", count($chunk)));
                $query = $this->queryFactory->create(
                    QueryInterface::TYPE_BOOL,
                    [
                        'must' => [
                            $this->getInvalidEventsQuery(),
                            $this->queryFactory->create(
                                QueryInterface::TYPE_TERMS,
                                ['field' => 'page.product.sku', 'values' => array_values($chunk)]
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
     * Cleanup sessions still having '0' in product_view by removing it.
     *
     * @param int $storeId Store id.
     *
     * @return void
     */
    private function cleanupUnfixedSessions($storeId)
    {
        // Remove remaining '0' from product_view sessions.
        $query = $this->queryFactory->create(
            QueryInterface::TYPE_TERM,
            ['field' => 'product_view', 'value' => 0]
        );
        $script = 'if (ctx._source.product_view.contains(0)) { ctx._source.product_view.remove(ctx._source.product_view.indexOf(0)) }';
        $params = [
            'index' => $this->indexSettings->getIndexAliasFromIdentifier(SessionIndexInterface::INDEX_IDENTIFIER, $storeId),
            /* 'type' => '_doc', */
            'body' => [
                'script' => [
                    'source' => $script,
                    'lang' => 'painless',
                ],
                'query' => $this->queryBuilder->buildQuery($query),
            ],
            'conflicts' => 'proceed',
            'wait_for_completion' => true,
        ];
        $this->client->updateByQuery($params);
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
     * Build a search request used to collect SKUs of invalid product view events with a product id of 0.
     * OR NOT Also collects the corresponding sessions of such invalid events.
     *
     * @param int $storeId Store id.
     *
     * @return RequestInterface
     */
    private function getInvalidEventsSkuRequest($storeId)
    {
        $queryFilters = [
            $this->getInvalidEventsQuery(),
        ];

        $skuAgg = $this->aggregationFactory->create(
            BucketInterface::TYPE_TERM,
            [
                'name'  => 'sku',
                'field' => 'page.product.sku.untouched',
                'size'  => BucketInterface::MAX_BUCKET_SIZE,
            ]
        );

        return $this->searchRequestBuilder->create(
            $storeId,
            EventIndexInterface::INDEX_IDENTIFIER,
            0,
            0,
            null,
            ['date' => ['direction' => SortOrderInterface::SORT_ASC]],
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
                'size'  => BucketInterface::MAX_BUCKET_SIZE,
            ]
        );

        return $this->searchRequestBuilder->create(
            $storeId,
            EventIndexInterface::INDEX_IDENTIFIER,
            0,
            0,
            null,
            ['date' => ['direction' => SortOrderInterface::SORT_ASC]],
            [],
            $queryFilters,
            [$sessionIdAgg]
        );
    }
}
