<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Event\Mapping\Update;

use OpenSearch\Common\Exceptions\Missing404Exception;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteTracker\Api\EventIndexInterface;
use Smile\ElasticsuiteTracker\Model\IndexManager;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexInterfaceFactory;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterfaceFactory;

/**
 * Order item update mapping model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class OrderItemDate
{
    /** @var FieldInterfaceFactory */
    private $fieldFactory;

    /** @var IndexManager */
    private $indexManager;

    /** @var IndexSettingsInterface */
    private $indexSettings;

    /** @var IndexInterfaceFactory */
    private $indexFactory;

    /** @var IndexOperationInterface */
    private $indexOperation;

    /** @var QueryFactory */
    private $queryFactory;

    /** @var QueryBuilder */
    private $queryBuilder;

    /** @var ClientInterface */
    private $client;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var LoggerInterface */
    private $logger;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param FieldInterfaceFactory   $fieldFactory   Field factory.
     * @param IndexManager            $indexManager   Index manager.
     * @param IndexSettingsInterface  $indexSettings  Index settings helper.
     * @param IndexInterfaceFactory   $indexFactory   Index factory.
     * @param IndexOperationInterface $indexOperation Index operation.
     * @param QueryFactory            $queryFactory   Query factory.
     * @param QueryBuilder            $queryBuilder   Query builder.
     * @param ClientInterface         $client         Elasticsuite client.
     * @param StoreManagerInterface   $storeManager   Store manager.
     * @param LoggerInterface         $logger         Logger.
     */
    public function __construct(
        FieldInterfaceFactory $fieldFactory,
        IndexManager $indexManager,
        IndexSettingsInterface $indexSettings,
        IndexInterfaceFactory $indexFactory,
        IndexOperationInterface $indexOperation,
        QueryFactory $queryFactory,
        QueryBuilder $queryBuilder,
        ClientInterface $client,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->fieldFactory     = $fieldFactory;
        $this->indexManager     = $indexManager;
        $this->indexSettings    = $indexSettings;
        $this->indexFactory     = $indexFactory;
        $this->indexOperation   = $indexOperation;
        $this->queryFactory     = $queryFactory;
        $this->queryBuilder     = $queryBuilder;
        $this->client           = $client;
        $this->storeManager     = $storeManager;
        $this->logger           = $logger;
    }

    /**
     * Return the list of indices that need to be updating with regards to the page.order.items.date
     * need to exist and being a 'date' field.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return array
     */
    public function checkIndices()
    {
        $indicesToFix = ['add' => [], 'fix' => []];

        foreach ($this->storeManager->getStores() as $store) {
            $storeId = $store->getId();
            $eventIndexAlias = $this->indexManager->getIndexAlias(EventIndexInterface::INDEX_IDENTIFIER, $storeId);
            try {
                $eventIndicesMappings = $this->client->getMapping($eventIndexAlias);
                foreach ($eventIndicesMappings as $eventIndexName => $mappingData) {
                    $mapping = $mappingData['mappings'];

                    // phpcs:ignore Generic.Files.LineLength
                    $dateFieldType = $mapping['properties']['page']['properties']['order']['properties']['items']['properties']['date']['type'] ?? null;
                    if (null === $dateFieldType) {
                        $indicesToFix['add'][] = ['index' => $eventIndexName, 'store' => $store->getId()];
                    } elseif ('date' === $dateFieldType) {
                        continue;
                    } else {
                        $indicesToFix['fix'][] = ['index' => $eventIndexName, 'store' => $store->getId()];
                    }
                }
            } catch (Missing404Exception $e) {
                // Tracker could simply be disabled and/or all indices removed.
                $this->logger->info(
                    sprintf("[%s] No tracking index %s found", __CLASS__, $eventIndexAlias)
                );
            }
        }

        if (empty($indicesToFix['add']) && empty($indicesToFix['fix'])) {
            $this->logger->info(
                sprintf('[%s] All tracker event indices have a correct mapping', __CLASS__)
            );
        }

        if (!empty($indicesToFix['add'])) {
            $this->logger->info(
                sprintf(
                    '[%s] Field page.order.items.date needs to be added to the mapping of those indices: %s',
                    __CLASS__,
                    implode(', ', array_column($indicesToFix['add'], 'index'))
                )
            );
        }

        if (!empty($indicesToFix['fix'])) {
            $this->logger->info(
                sprintf(
                    '[%s] Field page.order.items.date exists but needs to have its type changed in indices: %s',
                    __CLASS__,
                    implode(', ', array_column($indicesToFix['fix'], 'index'))
                )
            );
        }

        return $indicesToFix;
    }

    /**
     * Add the page.order.items.date field to a list of indices.
     *
     * @param array $indices Indices to add the field to.
     *
     * @return void
     */
    public function addFieldToIndices($indices = [])
    {
        $field = $this->fieldFactory->create([
            'name' => 'page.order.items.date',
            'type' => 'date',
            'nestedPath' => 'page.order.items',
        ]);
        $fieldMappingData = [
            'properties' => [
                'page' => [
                    'properties' => [
                        'order' => [
                            'properties' => [
                                'items' => [
                                    'properties' => [
                                        'date' => $field->getMappingPropertyConfig(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($indices as $indexData) {
            $indexName  = $indexData['index'];
            $this->logger->info(
                sprintf('[%s] Updating the mapping of existing index %s', __CLASS__, $indexName)
            );

            $mappingData = $this->client->getMapping($indexName);
            $mappingProper = $mappingData[$indexName]['mappings'];
            $mappingProper = array_merge_recursive($mappingProper, $fieldMappingData);
            $this->client->putMapping($indexName, $mappingProper);
        }
    }

    /**
     * Fix the page.order.items.date field type in indices where it already exists.
     *
     * @param array $indices Indices to fix the field type in.
     *
     * @return void
     * @throws \Exception
     */
    public function fixFieldTypeInIndices($indices = [])
    {
        foreach ($indices as $indexData) {
            $indexName  = $indexData['index'];
            $storeId    = $indexData['store'];
            $this->logger->info(
                sprintf('[%s] Preparing to replace the mapping of existing index %s', __CLASS__, $indexName)
            );

            $indexAlias = $this->indexSettings->getIndexAliasFromIdentifier(
                EventIndexInterface::INDEX_IDENTIFIER,
                $storeId
            );

            $newIndexName = $this->getNextAvailableIndexName($indexName);
            $this->logger->info(
                sprintf('[%s] Creating new index %s with correct mapping', __CLASS__, $newIndexName)
            );
            $newIndex = $this->createNewIndex($newIndexName, $storeId);

            $this->logger->info(
                sprintf('[%s] Re-indexing %s into %s', __CLASS__, $indexName, $newIndex->getName())
            );
            $response = $this->indexOperation->reindex(
                [$indexName],
                $newIndex->getName(),
                ['conflicts' => 'proceed']
            );

            if (isset($response['failures']) && !empty($response['failures'])) {
                $failures = json_encode($response['failures']);
                throw new \LogicException(
                    sprintf(
                        "Error during the reindex of %s into %s: %s",
                        $indexName,
                        $newIndex->getName(),
                        $failures
                    )
                );
            }

            if (isset($response['version_conflicts']) && $response['version_conflicts'] > 0) {
                $this->logger->info(
                    sprintf(
                        "Conflicts during the reindex of %s into %s",
                        $indexName,
                        $newIndex->getName()
                    ),
                    [
                        'conflicts count' => $response['version_conflicts'],
                        'source index'  => $indexName,
                        'dest index'    => $newIndex->getName(),
                    ]
                );
            }

            $this->logger->info(
                sprintf('[%s] Switching live index alias from %s into %s', __CLASS__, $indexName, $newIndex->getName())
            );
            // Remove the alias from the live index.
            $this->client->updateAliases(
                [
                    ['remove' => ['index' => $indexName, 'alias' => $indexAlias]],
                    ['add' => ['index' => $newIndex->getName(), 'alias' => $indexAlias]],
                ]
            );
            $this->logger->info(
                sprintf('[%s] Deleting original index %s', __CLASS__, $indexName)
            );
            $this->client->deleteIndex($indexName);
        }
    }

    /**
     * Make sure the event date is replicated at the page.order.items level in checkout_onepage_success events.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @return void
     */
    public function updateOrderItemDate()
    {
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = $store->getId();
            $eventIndexAlias = $this->indexManager->getIndexAlias(EventIndexInterface::INDEX_IDENTIFIER, $storeId);

            if ($this->client->indexExists($eventIndexAlias)) {
                $this->logger->info(
                    sprintf(
                        '[%s] Copying the checkout_one_page events date to the order items structure for %s indices',
                        __CLASS__,
                        $eventIndexAlias
                    )
                );

                $query = $this->queryFactory->create(
                    QueryInterface::TYPE_BOOL,
                    [
                        'must' => [
                            $this->queryFactory->create(
                                QueryInterface::TYPE_TERM,
                                ['field' => 'page.type.identifier', 'value' => 'checkout_onepage_success']
                            ),
                        ],
                        'mustNot' => [
                            $this->queryFactory->create(
                                QueryInterface::TYPE_NESTED,
                                [
                                    'path'  => 'page.order.items',
                                    'query' => $this->queryFactory->create(
                                        QueryInterface::TYPE_EXISTS,
                                        ['field' => 'page.order.items.date']
                                    ),
                                ]
                            ),
                        ],
                    ]
                );
                $params = [
                    'index' => $eventIndexAlias,
                    'wait_for_completion' => true,
                    'conflicts' => 'proceed',
                    'body' => [
                        'script' => [
                            'source' => "def targets = ctx._source.page.order.items; for(item in targets) { item.date = ctx._source.date }",
                            'lang' => 'painless',
                        ],
                        'query' => $this->queryBuilder->buildQuery($query),
                    ],
                ];

                $this->client->updateByQuery($params);
            } else {
                // Tracker could simply be disabled and/or all indices removed.
                $this->logger->info(
                    sprintf("[%s] No tracking index %s found", __CLASS__, $eventIndexAlias)
                );
            }
        }
    }

    /**
     * Try to get a new index name by appending a counter (_001, _002) at the end of the provided index name.
     * The counter is increased until no existing index already bears that name.
     *
     * @param string $indexName Original index name.
     *
     * @return string
     * @throws \Exception
     */
    private function getNextAvailableIndexName($indexName)
    {
        $attempt = 0;
        $maxAttempts = 255;
        do {
            $newIndexName = sprintf('%s_%03d', $indexName, $attempt);
            if (false === $this->client->indexExists($newIndexName)) {
                break;
            }
            $attempt++;
        } while ($attempt < $maxAttempts);

        if ($attempt == $maxAttempts) {
            throw new \Exception(sprintf("Too many copies of index %s to create a new copy", $indexName));
        }

        return $newIndexName;
    }

    /**
     * Create new event index.
     *
     * @param string $indexName Index name.
     * @param int    $storeId   Store Id.
     *
     * @return IndexInterface
     */
    private function createNewIndex($indexName, $storeId)
    {
        $indexSettings = $this->indexSettings->getIndicesConfig();
        $indexConfig = array_merge(
            ['identifier' => EventIndexInterface::INDEX_IDENTIFIER, 'name' => $indexName],
            $indexSettings[EventIndexInterface::INDEX_IDENTIFIER]
        );

        $index = $this->indexFactory->create($indexConfig);

        $indexSettings = $this->indexSettings->getCreateIndexSettings($index->getIdentifier());
        $indexSettings += $this->indexSettings->getDynamicIndexSettings($storeId);
        $indexSettings['analysis'] = $this->indexSettings->getAnalysisSettings($storeId);
        $this->client->createIndex($index->getName(), ['settings' => $indexSettings]);
        $this->client->putMapping($index->getName(), $index->getMapping()->asArray());

        return $index;
    }
}
