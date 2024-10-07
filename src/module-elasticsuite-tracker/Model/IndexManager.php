<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteTracker\Model;

use Magento\Framework\Stdlib\DateTime as MagentoDateTime;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteTracker\Api\EventIndexInterface;
use Smile\ElasticsuiteTracker\Api\SessionIndexInterface;
use Smile\ElasticsuiteTracker\Model\ResourceModel\SessionIndex;

/**
 * Tracking Indices Manager
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexManager
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface
     */
    private $indexSettings;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Client\ClientInterface
     */
    private $client;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface
     */
    private $indexOperation;

    /**
     * @var \Smile\ElasticsuiteTracker\Model\EventIndex\DateBounds
     */
    private $dateBounds;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexInterfaceFactory
     */
    private $indexFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface                $storeManager   Store Manager.
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface  $indexSettings  Index settings.
     * @param \Smile\ElasticsuiteCore\Api\Client\ClientInterface        $client         ES client.
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface $indexOperation Index operation.
     * @param \Smile\ElasticsuiteTracker\Model\EventIndex\DateBounds    $dateBounds     Date bounds.
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexInterfaceFactory   $indexFactory   Index factory.
     * @param \Psr\Log\LoggerInterface                                  $logger         The internal logger.
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface $indexSettings,
        \Smile\ElasticsuiteCore\Api\Client\ClientInterface $client,
        \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface $indexOperation,
        \Smile\ElasticsuiteTracker\Model\EventIndex\DateBounds $dateBounds,
        \Smile\ElasticsuiteCore\Api\Index\IndexInterfaceFactory $indexFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->storeManager   = $storeManager;
        $this->client         = $client;
        $this->indexSettings  = $indexSettings;
        $this->indexOperation = $indexOperation;
        $this->dateBounds     = $dateBounds;
        $this->indexFactory   = $indexFactory;
        $this->logger         = $logger;
    }

    /**
     * Create an event index if not exists.
     *
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexInterface $index Index.
     * @param int                                              $store Store id.
     *
     * @return void
     */
    public function createIndexIfNotExists(\Smile\ElasticsuiteCore\Api\Index\IndexInterface $index, $store)
    {
        if ($this->client->indexExists($index->getName()) === false) {
            $indexAlias = $this->getIndexAlias($index->getIdentifier(), $store);
            $indexSettings = array_merge(
                $this->indexSettings->getCreateIndexSettings($index->getIdentifier()),
                $this->indexSettings->getInstallIndexSettings($index->getIdentifier())
            );
            $indexSettings += $this->indexSettings->getDynamicIndexSettings($store);
            $indexSettings['analysis'] = $this->indexSettings->getAnalysisSettings($store);
            $this->client->createIndex($index->getName(), ['settings' => $indexSettings]);
            $this->client->updateAliases([['add' => ['index' => $index->getName(), 'alias' => $indexAlias]]]);
            $this->client->putMapping($index->getName(), $index->getMapping()->asArray());
        }
    }

    /**
     * Keep only the last $number tracking indices.
     *
     * @param int $number Number of indices to keep.
     *
     * @return void
     */
    public function keepLatest(int $number)
    {
        if ($number === 0) {
            return;
        }

        $indexIdentifiers = [SessionIndexInterface::INDEX_IDENTIFIER, EventIndexInterface::INDEX_IDENTIFIER];

        $thresholdDate = new \DateTime('first day of this month');
        $thresholdDate->setTime(0, 0);
        $thresholdDate->modify("-{$number} month");

        foreach ($indexIdentifiers as $indexIdentifier) {
            foreach ($this->storeManager->getStores() as $store) {
                $indexAlias = $this->getIndexAlias($indexIdentifier, $store->getId());
                $indices    = $this->client->getIndicesNameByAlias($indexAlias);
                $indicesToDelete = $this->getIndicesToDelete($thresholdDate, $indices, $indexAlias);

                if (!empty($indicesToDelete)) {
                    // In case of many indices existing, chunk the process.
                    foreach (array_chunk($indicesToDelete, 10) as $deleteChunk) {
                        $this->client->deleteIndex(implode(',', $deleteChunk));
                    }
                }
            }
        }
    }

    /**
     * Returns a [from, to] date range of available data based on horodated indices presence.
     *
     * @param string $indexIdentifier Index identifier.
     * @param int    $storeId         Store id.
     *
     * @return \DateTime[]
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getIndicesDateBounds($indexIdentifier, $storeId)
    {
        $bounds = [];

        $indexAlias = $this->getIndexAlias($indexIdentifier, $storeId);
        $indices    = $this->client->getIndicesNameByAlias($indexAlias);

        if (!empty($indices)) {
            // Indices could have been created but be empty after an elasticsuite:tracker:fix-data.
            $dateBounds = array_filter($this->dateBounds->getIndicesDateBounds());
            if (($dateBounds['minDate'] ?? null) && ($dateBounds['maxDate'] ?? null)) {
                $bounds = [
                    \DateTime::createFromFormat(MagentoDateTime::DATETIME_PHP_FORMAT, $dateBounds['minDate']),
                    \DateTime::createFromFormat(MagentoDateTime::DATETIME_PHP_FORMAT, $dateBounds['maxDate']),
                ];
            }
        }

        return $bounds;
    }

    /**
     * Build index name from an event.
     *
     * @param string $indexIdentifier Index identifier.
     * @param int    $storeId         Store id.
     * @param string $date            Date.
     *
     * @return string
     */
    public function getIndexName($indexIdentifier, $storeId, $date)
    {
        $indexAlias = $this->getIndexAlias($indexIdentifier, $storeId);
        $date       = substr($date, 0, 10);

        return sprintf("%s_%s", $indexAlias, str_replace("-", "", $date));
    }

    /**
     * Build index alias from an identifier & store.
     *
     * @param string $indexIdentifier Index identifier.
     * @param int    $storeId         Store id.
     *
     * @return string
     */
    public function getIndexAlias($indexIdentifier, $storeId)
    {
        return $this->indexSettings->getIndexAliasFromIdentifier($indexIdentifier, $storeId);
    }

    /**
     * Migrate daily indices to monthly indices.
     */
    public function migrateDailyToMonthlyIndices(): void
    {
        $indexIdentifiers = [SessionIndexInterface::INDEX_IDENTIFIER, EventIndexInterface::INDEX_IDENTIFIER];

        foreach ($indexIdentifiers as $indexIdentifier) {
            foreach ($this->storeManager->getStores() as $store) {
                $storeId = $store->getId();
                $indexAlias = $this->getIndexAlias($indexIdentifier, $storeId);
                $indices = $this->client->getIndicesNameByAlias($indexAlias);
                $indicesByMonth = $this->getDailyIndicesByMonth($indices, $indexAlias);

                foreach ($indicesByMonth as $date => $dailyMonthIndices) {
                    $monthIndex = $this->getIndex($indexIdentifier, $store->getId(), $date);
                    if ($monthIndex !== null) {
                        $response = $this->indexOperation->reindex(
                            $dailyMonthIndices,
                            $monthIndex->getName(),
                            ['conflicts' => 'proceed']
                        );

                        if (isset($response['failures']) && !empty($response['failures'])) {
                            $failures = json_encode($response['failures']);
                            throw new \LogicException(
                                "Error during the merge of {$indexIdentifier} indices: {$failures}"
                            );
                        }

                        if (isset($response['version_conflicts']) && $response['version_conflicts'] > 0) {
                            $this->logger->info("Conflicts during the merger of {$indexIdentifier} indices", [
                                'conflicts count' => $response['version_conflicts'],
                                'source indices'  => $dailyMonthIndices,
                                'dest index'      => $monthIndex->getName(),
                            ]);
                        }

                        $this->client->deleteIndex(implode(',', $dailyMonthIndices));
                    }
                }
            }
        }
    }

    /**
     * Get index by identifier, store and date.
     *
     * @param string $indexIdentifier Index identifier.
     * @param int    $storeId         Store id.
     * @param string $date            Date.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\IndexInterface|null
     */
    public function getIndex(string $indexIdentifier, int $storeId, string $date)
    {
        try {
            $indexName  = $this->getIndexName($indexIdentifier, $storeId, $date);

            $indexSettings = $this->indexSettings->getIndicesConfig();
            $indexConfig = array_merge(
                ['identifier' => $indexIdentifier, 'name' => $indexName],
                $indexSettings[$indexIdentifier]
            );

            $index = $this->indexFactory->create($indexConfig);
            $this->createIndexIfNotExists($index, $storeId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $index = null;
        }

        return $index;
    }

    /**
     * Get daily indices by month.
     *
     * @param array  $dailyIndices Daily indices.
     * @param string $indexAlias   Index alias.
     *
     * @return array
     */
    protected function getDailyIndicesByMonth(array $dailyIndices, string $indexAlias): array
    {
        $indicesByMonth = [];
        foreach ($dailyIndices as $dailyIndex) {
            $date = str_replace("{$indexAlias}_", '', $dailyIndex);
            if (strlen($date) > 6 && substr($date, 0, 6) !== false) {
                $indicesByMonth[substr($date, 0, 6)][] = $dailyIndex;
            }
        }

        return $indicesByMonth;
    }

    /**
     * Get indices to delete.
     *
     * @param \DateTime $thresholdDate Threshold date.
     * @param array     $indices       Indices.
     * @param string    $indexAlias    Index alias.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function getIndicesToDelete(\DateTime $thresholdDate, array $indices, string $indexAlias): array
    {
        $indicesToDelete = [];
        foreach ($indices as $index) {
            $date = str_replace("{$indexAlias}_", '', $index);
            $indexDate = null;

            if (strlen($date) == 6) {
                $indexDate  = \DateTime::createFromFormat('Ymd', $date . '01');
            }

            if ($indexDate === null) {
                $indexDate  = \DateTime::createFromFormat('Ymd', $date);
            }

            $indexDate->setTime(0, 0);
            if ($indexDate < $thresholdDate) {
                $indicesToDelete[] = $index;
            }
        }

        return $indicesToDelete;
    }
}
